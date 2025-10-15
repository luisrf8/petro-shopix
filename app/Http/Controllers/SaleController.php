<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ProductVariant;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\Category;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Mail\OrderPdfMail;  // Importa la clase correctamente
use App\Mail\PaymentConfirmationMail;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use App\Models\DollarRate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SaleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $customerId = $user;
        // Traer todos los productos con sus variantes
        $productItems = Product::with(['category', 'images', 'variants'])
        ->where('tenant_id', $user->tenant_id)
        ->orderBy('created_at', 'desc')->get();
        $paymentMethods = PaymentMethod::with('currency')->where('tenant_id', $user->tenant_id)->get();
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $user->tenant_id)->first();

        // Traer todas las categorías
        $categories = Category::where('tenant_id', $user->tenant_id)->get();

        return view('sales', compact('categories', 'paymentMethods', 'productItems', 'dollarRate', 'customerId'));
    }
    
    public function store(Request $request)
    {
        // Decodificar customerId si viene como JSON string
        $customer = is_string($request->customerId) ? json_decode($request->customerId, true) : $request->customerId;
        $customerId = is_array($customer) ? $customer['id'] : null;
        $tenantId = $request->tenant_id;
        $itemsSelected = $request->items;
        $paymentDetails = $request->payments ?? [];

        if (!$customerId) {
            return response()->json(['error' => 'ID de cliente no válido.'], 400);
        }

        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }

        // Agrupar productos
        $groupedData = [];
        foreach ($itemsSelected as $item) {
            $groupedData[] = [
                'product_variant_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $item['price'] * $item['quantity'],
            ];
        }

        // Crear orden de venta
        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'date' => now()->toDateString(),
            'status' => 1,
            'address' => 'Tienda',
            'preference' => 'Tienda',
            'tenant_id' => $tenantId,
        ]);

        // Crear detalles y actualizar stock
        foreach ($groupedData as $detail) {
            SalesOrderDetail::create([
                'sales_order_id' => $salesOrder->id,
                'product_variant_id' => $detail['product_variant_id'],
                'quantity' => $detail['quantity'],
                'price' => $detail['price'],
                'amount' => $detail['amount'],
            ]);

            $productVariant = ProductVariant::find($detail['product_variant_id']);
            if ($productVariant && $productVariant->stock >= $detail['quantity']) {
                $productVariant->stock -= $detail['quantity'];
                $productVariant->save();
            } else {
                return response()->json(['error' => 'Stock insuficiente para el producto: ' . $productVariant->id], 400);
            }
        }

        // Crear pagos (si existen)
        $approvedPayments = collect();
        if (!empty($paymentDetails) && is_array($paymentDetails)) {
            foreach ($paymentDetails as $paymentDetail) {
                $payment = Payment::create([
                    'sales_order_id' => $salesOrder->id,
                    'payment_method' => $paymentDetail['methodId'],
                    'amount' => $paymentDetail['amount'],
                    'currency' => $paymentDetail['currency'],
                    'reference' => $paymentDetail['reference'] ?? null,
                    'status' => $paymentDetail['status'] ?? 1, // Por defecto aprobado
                ]);

                if ($payment->status == 1) {
                    $approvedPayments->push($payment);
                }
            }
        }

        // Recuperar orden con relaciones
        $order = SalesOrder::with([
            'user',
            'details',
            'details.variant.product',
            'payments.payment'
        ])->findOrFail($salesOrder->id);

        // ⚠️ Generar PDF solo si hay pagos aprobados
        if ($approvedPayments->isNotEmpty()) {
            $serverIp = request()->getHost();

            $imagePath = storage_path('app/public/products/infblack.png');
            $imageData = base64_encode(file_get_contents($imagePath));
            $imageBase64 = 'data:image/png;base64,' . $imageData;

            $totalOrden = $order->details->sum('amount');
            $totalPagado = $order->payments->sum('amount');

            // Generar QR
            $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";
            $qrCode = QrCode::create($qrUrl)
                ->setEncoding(new Encoding('UTF-8'))
                ->setSize(250)
                ->setMargin(10);

            $writer = new PngWriter();
            $qrCodeImage = $writer->write($qrCode);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());

            // Generar PDF
            $pdfContent = view('orderPdf', compact('order', 'totalOrden', 'totalPagado', 'imageBase64', 'qrCodeBase64'))->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($pdfContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Guardar PDF
            $fileName = 'orden-' . $order->id . '.pdf';
            Storage::disk('public')->put('orders/' . $fileName, $dompdf->output());
            $pdfUrl = asset('storage/orders/' . $fileName);
        } else {
            $pdfUrl = null;
        }

        return response()->json([
            'message' => $approvedPayments->isNotEmpty()
                ? 'Venta registrada exitosamente con pagos aprobados.'
                : 'Venta registrada sin pagos aprobados (PDF no generado).',
            'pdf_url' => $pdfUrl
        ], 200);
    }

    public function downloadPdf($id)
    {
        $order = SalesOrder::with([
            'user',
            'details.variant.product',
            'payments.payment'
        ])->findOrFail($id);

        $totalOrden = $order->details->sum('amount');
        $totalPagado = $order->payments->sum('amount');

        $serverIp = request()->getHost();
        $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";

        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);
        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());

        $imagePath = storage_path('app/public/products/infblack.png');
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageBase64 = 'data:image/png;base64,' . $imageData;

        $pdfContent = view('orderPdf', compact('order', 'totalOrden', 'totalPagado', 'imageBase64', 'qrCodeBase64'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($pdfContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="orden-' . $order->id . '.pdf"');
    }

    public function storeEcommerceSale(Request $request)
    {
        // Validar los datos recibidos
        $itemsSelected = json_decode($request->itemsSelected, true); // Convertir JSON a arreglo
        $paymentDetails = $request->paymentDetails;
        $totalAmount = $request->totalAmount;
        $customerId = $request->customer_id;
        $preference = $request->preference;
        $address = $request->direccion;
    
        // Validación de productos
        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
    
        // Validación de pagos
        if (empty($paymentDetails) || !is_array($paymentDetails)) {
            return response()->json(['error' => 'No se enviaron detalles de pago válidos.'], 400);
        }
    
        // Agrupar y procesar datos de productos
        $groupedData = [];
        foreach ($itemsSelected as $item) {
            $variant = $item['item']; // Accedemos a la información del producto
            $groupedData[] = [
                'product_variant_id' => $variant['id'],
                'quantity' => $item['quantity'],
                'price' => $variant['price'],
                'amount' => $variant['price'] * $item['quantity'],
            ];
        }
    
        // Crear orden de venta con status en 0 (pendiente)
        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'date' => now()->toDateString(),
            'status' => 0, // Pendiente por defecto en eCommerce
            'address' => $address ?? 'Tienda',
            'preference' => $preference,
        ]);
    
        // Crear detalles de la venta y actualizar stock
        foreach ($groupedData as $detail) {
            SalesOrderDetail::create([
                'sales_order_id' => $salesOrder->id,
                'product_variant_id' => $detail['product_variant_id'],
                'quantity' => $detail['quantity'],
                'price' => $detail['price'],
                'amount' => $detail['amount'],
            ]);
    
            // Actualizar el stock
            $productVariant = ProductVariant::find($detail['product_variant_id']);
            if ($productVariant && $productVariant->stock >= $detail['quantity']) {
                $productVariant->stock -= $detail['quantity'];
                $productVariant->save();
            } else {
                return response()->json(['error' => 'Stock insuficiente para el producto: ' . $productVariant->id], 400);
            }
        }
    
        // Crear pagos
        foreach ($paymentDetails as $paymentDetail) {
            $paymentDetailMethod = json_decode($paymentDetail['method'], true);// Convertir JSON a arreglo
            $currencyCode = Currency::where('name', $paymentDetail['currency'])->value('code');
            $payment = Payment::create([
                'sales_order_id' => $salesOrder->id,
                'payment_method' => $paymentDetailMethod['id'], // Se usa referencia en eCommerce
                'amount' => $paymentDetail['amount'],
                'reference' => $paymentDetail['reference'],
                'currency' => $currencyCode, // Se usa el código de la moneda
                'payment_date' => $paymentDetail['paymentDate'],
            ]);
    
            // Subir imagen (comprobante de pago) si existe
            if ($request->hasFile('paymentDetails.*.img')) {
                // Iterar sobre el arreglo de detalles de pago
                foreach ($paymentDetails as $key => $paymentDetail) {
                    // Comprobar si existe un archivo 'img'
                    if ($request->hasFile("paymentDetails.$key.img")) {
                        $image = $request->file("paymentDetails.$key.img");
                        $path = $image->store('payment_images', 'public');
                        
                        // Guardar la ruta de la imagen asociada al pago
                        PaymentImage::create([
                            'payment_id' => $payment->id,
                            'image_path' => $path,
                        ]);
                    }
                }
            }
        }
    
        return response()->json(['message' => 'Venta en eCommerce registrada exitosamente.'], 200);
    }
    

    public function viewOrders()
    {
        $user = auth()->user();
        $salesOrders = SalesOrder::with([
            'user', 
            'details', 
            'details.variant', 
            'payments' // Agregamos la relación de pagos
        ])->where('tenant_id', $user->tenant_id)->orderBy('id', 'desc')->get();

        foreach ($salesOrders as $order) {
            $order->total_items = $order->details->sum('quantity');
        }
    
        return view('salesOrders', compact('salesOrders'));
    }

    public function viewOrdersReport(Request $request)
    {
        $range = $request->input('range', 'monthly');
        $today = Carbon::today();

        switch ($range) {
            case 'weekly':
                $startDate = $today->copy()->startOfWeek();
                $rangoDescriptivo = 'la última semana';
                break;
            case 'monthly':
                $startDate = $today->copy()->startOfMonth();
                $rangoDescriptivo = 'el último mes';
                break;
            case 'quarterly':
                $startDate = $today->copy()->subMonths(3)->startOfDay();
                $rangoDescriptivo = 'los últimos 3 meses';
                break;
            case 'yearly':
                $startDate = $today->copy()->startOfYear();
                $rangoDescriptivo = 'el último año';
                break;
            default:
                $startDate = $today->copy()->startOfMonth();
                $rangoDescriptivo = 'el último mes';
        }

        $salesOrders = SalesOrder::with([
            'user',
            'details',
            'details.variant',
            'payments'
        ])
        ->whereDate('created_at', '>=', $startDate)
        ->orderBy('id', 'desc')
        ->get();

        foreach ($salesOrders as $order) {
            $order->total_items = $order->details->sum('quantity');
        }


        $pdfContent = view('salesOrdersReport', compact('salesOrders', 'rangoDescriptivo', 'startDate', 'range'))->render();
        
            // Configuración de Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($pdfContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $fecha = now()->format('d-m-Y_His');
            $fileName = 'reporte_ordenes_ventas_' . $fecha . '.pdf';
            Storage::disk('public')->put('reports/' . $fileName, $dompdf->output());
            $filePath = storage_path('app/public/reports/' . $fileName);

            $pdfUrl = asset('storage/reports/' . $fileName);

        return response()->json([
            'success' => true,
            'message' => 'Reporte generado',
            'pdf_url' => $pdfUrl,
            'fecha' => $fecha
        ]);
    }

    public function viewUserOrders($id)
    {
        $salesOrders = SalesOrder::with([
            'user', 
            'details', 
            'details.variant', 
            'payments',
            'payments.payment' // método de pago completo
        ])->where('user_id', $id) // Filtrar por usuario específico
        ->orderBy('date', 'desc')
        ->get();

        foreach ($salesOrders as $order) {
            $order->total_items = $order->details->sum('quantity');
        }

        return response()->json($salesOrders);
    }


    public function showByOrder($id)
    {
        $order = SalesOrder::with(['user', 'details', 'details.variant','details.variant.product', 'payments', 'payments.payment'])->find($id);
        // Calcular el total de la orden
        $totalOrden = $order->details->sum(function ($detalle) {
            return $detalle->amount;
        });
        // Calcular el total pagado
        $totalPagado = $order->payments->sum(function ($payment) {
            return $payment->amount;
        });
        $totalDevuelto = $order->returns->flatMap->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        $totalOrden = $order->details->sum('amount') - $totalDevuelto;
        $totalPagado = $order->payments->sum('amount');
        $saldo = $totalOrden - $totalPagado; // si es negativo, se debe dar vuelto
        $order->saldo = $saldo; // Agregar saldo al objeto de la orden
        $order->total_devuelto = $totalDevuelto; // Agregar total devuelto al objeto de la orden
        $order->total_pagado = $totalPagado; // Agregar total pagado al objeto de la orden
        $order->total_orden = $totalOrden; // Agregar total de la orden al objeto de la orden
        $order->has_returns = $order->returns->isNotEmpty(); // Verificar si tiene devoluciones
        return view('salesOrderDetail', compact('order', 'totalOrden', 'totalPagado'));
    }
    public function showPublicOrder($id)
    {
        $order = SalesOrder::with(['user', 'details', 'details.variant','details.variant.product', 'payments', 'payments.payment'])->find($id);
        // Calcular el total de la orden
        $totalOrden = $order->details->sum(function ($detalle) {
            return $detalle->amount;
        });
        // Calcular el total pagado
        $totalPagado = $order->payments->sum(function ($payment) {
            return $payment->amount;
        });

        return view('orderInfoQr', compact('order', 'totalOrden', 'totalPagado'));
    }

    public function getPaymentMethods()
    {
        $paymentMethods = PaymentMethod::with('currency')->get();
        // Agrupar métodos de pago por moneda
        $groupedPaymentMethods = $paymentMethods->groupBy(function ($paymentMethod) {
            return $paymentMethod->currency->name; // Agrupar por el nombre de la moneda
        });
        
        return response()->json($paymentMethods, 200);
    }
    public function getPaymentMethodsEcomm()
    {
        // Obtener los métodos de pago que están activos (por ejemplo, con status = 1)
        $paymentMethods = PaymentMethod::with('currency')
            ->where('status', 1) // Filtrar por métodos de pago activos
            ->get();
    
        // Filtrar los métodos de pago para excluir "Efectivo" y "Punto de Venta"
        $filteredPaymentMethods = $paymentMethods->filter(function ($paymentMethod) {
            return !in_array($paymentMethod->name, ['Efectivo', 'Punto de Venta']);
        });
    
        // Agrupar los métodos de pago filtrados por la moneda
        $groupedPaymentMethods = $filteredPaymentMethods->groupBy(function ($paymentMethod) {
            return $paymentMethod->currency->name; // Agrupar por el nombre de la moneda
        });
    
        // Convertir la colección agrupada a un array
        $formattedPaymentMethods = $groupedPaymentMethods->mapWithKeys(function ($group, $key) {
            return [$key => $group]; // Asignar la moneda como clave y los métodos de pago como valor
        });
    
        // Devolver la respuesta JSON con los métodos de pago agrupados por moneda
        return response()->json($formattedPaymentMethods, 200);
    }

    public function getVariants(Request $request)
    {
        $itemIds = $request->input('item_ids');
        
        // Validar que se reciban IDs válidos
        if (empty($itemIds) || !is_array($itemIds)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
        
        // Obtener variantes y productos, agrupando las variantes por producto
        $variants = ProductVariant::with('product')  // Cargar la relación con el producto
            ->whereIn('product_id', $itemIds)
            ->get();
    
        $groupedVariants = $variants->groupBy('product_id')->map(function ($group, $productId) {
            // Obtener la información del producto
            $product = $group->first()->product;  // Asumiendo que todas las variantes son del mismo producto
    
            return [
                'product_id' => $productId,
                'product_name' => $product->name,  // Obtener el nombre del producto
                'product_description' => $product->description,  // Obtener la descripción del producto (ajustar según tu modelo)
                'variants' => $group->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'type' => $variant->type,
                        'size' => $variant->size,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                    ];
                }),
            ];
        })->values();
        
        // Devolver solo los datos esperados
        return response()->json($groupedVariants, 200);
    }

    public function orderToggleStatus($id, Request $request)
    {
        // Recuperar la orden con sus relaciones
        $order = SalesOrder::with([
            'user', 
            'details', 
            'details.variant.product', 
            'payments.payment'
        ])->findOrFail($id);
    
        // Actualizar el estado de la orden
        $order->status = $request->status;
        $order->save();
    
        // Si el nuevo estado es 1, generar el PDF y enviar el correo
        if ($order->status == 1) {
            $serverIp = request()->getHost(); // Obtiene la IP o dominio del servidor

            // Cargar la imagen y convertirla a base64
            $imagePath = storage_path('app/public/products/infblack.png');
            $imageData = base64_encode(file_get_contents($imagePath));
            $imageBase64 = 'data:image/png;base64,' . $imageData;
    
            // Calcular totales
            $totalOrden = $order->details->sum('amount');
            $totalPagado = $order->payments->sum('amount');
    
            // Generar el código QR correctamente con Endroid QR Code
            $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";
            
            $qrCode = QrCode::create($qrUrl)
                ->setEncoding(new Encoding('UTF-8'))
                ->setSize(250)
                ->setMargin(10);
    
            $writer = new PngWriter();
            $qrCodeImage = $writer->write($qrCode);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());
    
            // Generar el HTML para el PDF con el QR y otros datos
            $pdfContent = view('orderPdf', compact('order', 'totalOrden', 'totalPagado', 'imageBase64', 'qrCodeBase64'))->render();
    
            // Configuración de Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($pdfContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
    
            // Guardar el PDF en storage/app/public/orders/
            $fileName = 'orden-' . $order->id . '.pdf';
            Storage::disk('public')->put('orders/' . $fileName, $dompdf->output());
            $filePath = storage_path('app/public/orders/' . $fileName);
    
            // URL accesible del PDF
            $pdfUrl = asset('storage/orders/' . $fileName);
    
            // Enviar el correo con el PDF generado
            // Mail::to($order->user->email)->send(new OrderPdfMail($order, $filePath));
    
            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada, PDF generado y correo enviado.',
                'pdf_url' => $pdfUrl
            ]);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Orden actualizada, pero no se generó PDF ni se envió correo.'
        ]);
    }
    public function orderDeliverToggleStatus($id, Request $request)
    {
        // Recuperar la orden con sus relaciones
        $order = SalesOrder::with([
            'user', 
            'details', 
            'details.variant.product', 
            'payments.payment'
        ])->findOrFail($id);
    
        // Actualizar el estado de la orden
        $order->deliver_status = $request->status;
        $order->save();
    
        // Si el nuevo estado es 1, enviar el correo de confirmación
        if ($order->status == 1 && $order->preference == "Envio") {
            // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
        
            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada y correo de confirmación enviado.'
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Orden actualizada.'
        ]);
    }
    

    public function paymentToggleStatus($id, Request $request)
    {
        // Buscar el pago
        $payment = Payment::findOrFail($id);
        // Cambiar el estado del pago
        $payment->status = $request->status;
        $payment->save();
        // Enviar correo de confirmación si el pago es aprobado
        if ($payment->status == 1) {
            // Mail::to($request->email)->send(new PaymentConfirmationMail($payment));
            return response()->json([
                'status' => 'success',
                'new_status' => $payment->status,
                'message' => 'Pago actualizado y correo enviado.'

            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado, pero no se envió correo.'
            ]);
        }
    }
    public function processReturn(Request $request, $orderId)
    {
        $order = SalesOrder::with('details')->findOrFail($orderId);
        $itemsToReturn = $request->input('items'); // array de items con id y cantidad
        $reason = $request->input('reason');

        if (empty($itemsToReturn)) {
            return response()->json(['error' => 'No se especificaron productos a devolver.'], 400);
        }

        $return = SalesReturn::create([
            'sales_order_id' => $order->id,
            'reason' => $reason,
        ]);

        foreach ($itemsToReturn as $item) {
            $detail = $order->details->where('product_variant_id', $item['id'])->first();

            if (!$detail || $item['quantity'] > $detail->quantity) {
                return response()->json(['error' => 'Cantidad inválida para devolver.'], 400);
            }

            // Registrar item devuelto
            SalesReturnItem::create([
                'sales_return_id' => $return->id,
                'product_variant_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $detail->price,
            ]);

            // Actualizar inventario
            $variant = ProductVariant::find($item['id']);
            $variant->stock += $item['quantity'];
            $variant->save();
        }

        // Marcar la orden como que tiene devolución
        $order->has_returns = true;
        $order->save();

        return response()->json(['message' => 'Devolución registrada exitosamente.']);
    }

}
