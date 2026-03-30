<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ProductVariant;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\PaymentImage;
use App\Models\Currency;
use App\Models\Category;
use App\Models\Product;
use App\Models\MaterialPackage;
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
use App\Models\EuroRate;
use App\Models\Tax;
use App\Models\Tenant;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use App\Models\ElectronicDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Support\WorkflowNotifier;
use App\Support\ImageStorage;
use App\Support\TenantCurrency;
use App\Services\TheFactoryHkaService;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $customerId = $user;
        // Traer todos los productos con sus variantes
        $productItems = Product::with(['category', 'images', 'variants', 'taxes'])
        ->where('tenant_id', $user->tenant_id)
        ->where('is_active', true)
        ->orderBy('created_at', 'desc')->get();
        $materialPackages = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images', 'items.variant.product.taxes'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        $paymentMethods = PaymentMethod::with('currency')
            ->where('tenant_id', $user->tenant_id)
            ->active()
            ->get();
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $user->tenant_id)->first();
        $euroRate = EuroRate::latest('created_at')->where('tenant_id', $user->tenant_id)->first();
        $taxes = Tax::all();
        $tenant = Tenant::find($user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $baseCurrencySymbol = TenantCurrency::resolveCurrencySymbol($baseCurrencyCode);
        $baseRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, $baseCurrencyCode);

        if ($baseRateToBs <= 0) {
            $baseRateToBs = (float) ($baseCurrencyCode === 'EUR' ? ($euroRate?->rate ?? 0) : ($dollarRate?->rate ?? 0));
        }

        $ratePayload = (object) ['rate' => $baseRateToBs];

        // Traer todas las categorías
        $categories = Category::where('tenant_id', $user->tenant_id)
        ->where('is_active', true)
        ->get();

        $customerRoleIds = $this->resolveCustomerRoleIds();
        $existingCustomersForSale = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', 1)
            ->whereIn('role_id', $customerRoleIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone_number', 'dni']);

        if ($categories->isEmpty()) {
            return redirect()->route('categories.index')
                ->with('warning', 'Debes crear al menos una categoría antes de registrar ventas.');
        }

        return view('sales', compact('categories', 'paymentMethods', 'productItems', 'materialPackages', 'dollarRate', 'euroRate', 'customerId', 'taxes', 'tenant', 'baseCurrencyCode', 'baseCurrencySymbol', 'baseRateToBs', 'ratePayload', 'existingCustomersForSale'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_type' => 'nullable|in:pickup,shipping',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_city_id' => 'nullable|integer|exists:cities,id',
            'sale_document_mode' => 'nullable|in:delivery_note,electronic_invoice',
            'create_new_customer' => 'nullable|boolean',
            'customer_existing_id' => 'nullable|integer|required_unless:create_new_customer,1',
            'customer_new' => 'nullable|array',
            'customer_new.name' => 'required_if:create_new_customer,1|string|max:255',
            'customer_new.email' => 'required_if:create_new_customer,1|email|unique:users,email',
            'customer_new.phone_number' => 'required_if:create_new_customer,1|string|max:20',
            'customer_new.dni' => 'required_if:create_new_customer,1|string|max:100',
            'mark_delivered' => 'nullable|boolean',
            'mark_payments_paid' => 'nullable|boolean',
            'mark_sale_completed' => 'nullable|boolean',
        ]);

        $tenantId = (int) (optional(auth()->user())->tenant_id ?? $request->tenant_id);
        $createNewCustomer = (bool) ($validated['create_new_customer'] ?? false);
        $customerId = null;
        $createdCustomerTemporaryPassword = null;

        if ($tenantId <= 0) {
            return response()->json(['error' => 'No se pudo determinar la tienda para registrar la venta.'], 422);
        }

        if ($createNewCustomer) {
            $customerPayload = $validated['customer_new'] ?? [];
            $customerRoleId = $this->resolveCustomerRoleId();
            $defaultCustomerPassword = '12345678';

            $createdCustomer = User::create([
                'name' => trim((string) ($customerPayload['name'] ?? 'Cliente')),
                'email' => trim((string) ($customerPayload['email'] ?? '')),
                'phone_number' => trim((string) ($customerPayload['phone_number'] ?? '')),
                'dni' => trim((string) ($customerPayload['dni'] ?? '')),
                'password' => Hash::make($defaultCustomerPassword),
                'tenant_id' => $tenantId,
                'role_id' => $customerRoleId,
                'is_active' => 1,
            ]);

            $customerId = (int) $createdCustomer->id;
            $createdCustomerTemporaryPassword = $defaultCustomerPassword;
        } else {
            $selectedCustomerId = (int) ($validated['customer_existing_id'] ?? 0);
            $customerRoleIds = $this->resolveCustomerRoleIds();

            $existingCustomer = User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', 1)
                ->whereIn('role_id', $customerRoleIds)
                ->find($selectedCustomerId);

            if (!$existingCustomer) {
                return response()->json(['error' => 'Debes seleccionar un cliente existente válido.'], 422);
            }

            $customerId = (int) $existingCustomer->id;
        }

        $itemsSelected = $request->items;
        $paymentDetails = $request->payments ?? [];
        $dollarRate = $request->dollarRate;
        $deliveryType = $validated['delivery_type'] ?? 'pickup';
        $deliveryAddress = trim((string) ($validated['delivery_address'] ?? ''));
        $markDelivered = (bool) ($validated['mark_delivered'] ?? false);
        $markPaymentsPaid = (bool) ($validated['mark_payments_paid'] ?? false);
        $markSaleCompleted = (bool) ($validated['mark_sale_completed'] ?? false);
        $requestedDocumentMode = (string) ($validated['sale_document_mode'] ?? 'delivery_note');

        if ($deliveryType === 'shipping' && $deliveryAddress === '') {
            return response()->json(['error' => 'La dirección es obligatoria para entregas por envío.'], 422);
        }

        $tienda = Tenant::find($tenantId);
        if (!$tienda) {
            return response()->json(['error' => 'No se encontró la tienda asociada a la venta.'], 422);
        }

        if ($deliveryType === 'shipping' && (bool) ($tienda->restrict_delivery_city_to_tenant ?? true)) {
            $deliveryCityId = (int) ($validated['delivery_city_id'] ?? 0);
            $shippingCityValidation = $this->validateShippingCityAgainstTenant($tienda, $deliveryCityId);

            if (!($shippingCityValidation['ok'] ?? false)) {
                return response()->json(['error' => (string) ($shippingCityValidation['message'] ?? 'Solo se permiten envíos a la ciudad de la tienda.')], 422);
            }
        }

        $preference = $deliveryType === 'shipping' ? 'Envío' : 'Retiro en tienda';
        $address = $deliveryType === 'shipping' ? $deliveryAddress : 'Tienda';

        if (!$customerId) {
            return response()->json(['error' => 'ID de cliente no válido.'], 400);
        }

        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
        $saleCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tienda);

        if ($requestedDocumentMode === 'electronic_invoice' && !(bool) ($tienda?->electronic_invoicing_enabled ?? false)) {
            return response()->json(['error' => 'La facturacion digital esta desactivada para esta tienda.'], 422);
        }

        $documentIssueMode = $requestedDocumentMode === 'electronic_invoice' ? 'electronic_invoice' : 'delivery_note';

        // Crear orden de venta
        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'date' => now()->toDateString(),
            'status' => $markSaleCompleted ? 1 : 0,
            'address' => $address,
            'preference' => $preference,
            'deliver_status' => $markDelivered ? 1 : 0,
            'tenant_id' => $tenantId,
            'document_issue_mode' => $documentIssueMode,
            'sale_currency_code' => $saleCurrencyCode,
        ]);

        // Crear detalles y actualizar stock
        foreach ($itemsSelected as $item) {
            $productVariant = ProductVariant::with('product')->find($item['id']);
            if (!$productVariant) {
                return response()->json(['error' => 'Variante no encontrada: ' . $item['id']], 400);
            }

            if ($productVariant->stock < $item['quantity']) {
                return response()->json(['error' => 'Stock insuficiente para el producto: ' . $item['id']], 400);
            }

            $baseUnitPrice = $this->getVariantDiscountedUnitPrice($productVariant);
            $lineDiscountPercentage = max(0, min(100, (float) ($item['line_discount_percentage'] ?? 0)));
            $unitPrice = round($baseUnitPrice * ((100 - $lineDiscountPercentage) / 100), 2);

            $salesDetail = SalesOrderDetail::create([
                'sales_order_id' => $salesOrder->id,
                'product_variant_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $unitPrice,
                'amount' => $unitPrice * $item['quantity'],
            ]);
            // ===========================
            //   GUARDAR TAXES POR ITEM
            // ===========================
            if (!empty($item['taxes'])) {
                foreach ($item['taxes'] as $tax) {

                    $rate = floatval($tax['rate']); 
                    $base = $unitPrice * $item['quantity'];
                    $amount = $base * ($rate / 100);

                    $salesDetail->taxes()->create([
                        'tax_name'  => $tax['name'],
                        'tax_rate'  => $rate,
                        'tax_amount'=> $amount,
                    ]);

                }
            }
            // Actualizar stock
            $productVariant->stock -= $item['quantity'];
            $productVariant->save();
        }

        // Crear pagos
        $approvedPayments = collect();
        if (!empty($paymentDetails) && is_array($paymentDetails)) {
            foreach ($paymentDetails as $paymentDetail) {
                $paymentMethod = PaymentMethod::with('currency')
                    ->where('tenant_id', $tenantId)
                    ->active()
                    ->find($paymentDetail['methodId']);

                if (!$paymentMethod) {
                    return response()->json(['error' => 'Uno de los métodos de pago seleccionados está inactivo o no pertenece a esta tienda.'], 422);
                }

                $payment = Payment::create([
                    'sales_order_id' => $salesOrder->id,
                    'payment_method' => $paymentMethod->id,
                    'amount' => $paymentDetail['amount'],
                    'currency' => $paymentMethod->currency->code ?? $paymentDetail['currency'],
                    'reference' => $paymentDetail['reference'] ?? null,
                    'status' => $markPaymentsPaid ? 1 : 0,
                ]);

                if ($payment->status == 1) {
                    $approvedPayments->push($payment);
                }
            }
        }

        // Recuperar la orden con relaciones + taxes
        $order = SalesOrder::with([
            'user',
            'details',
            'details.taxes.tax',
            'details.variant.product',
            'payments.payment'
        ])->findOrFail($salesOrder->id);

    // =====================================
    //   GENERAR PDF SI HAY PAGOS APROBADOS
    // =====================================
        if ($approvedPayments->isNotEmpty()) {
            $serverIp = request()->getHost();

            $imagePath = storage_path('app/public/products/infblack.png');
            $imageData = base64_encode(file_get_contents($imagePath));
            $imageBase64 = 'data:image/png;base64,' . $imageData;

            // Totales
            $totalOrden = $order->details->sum('amount');
            $totalTaxes = $order->details->flatMap->taxes->sum('tax_amount');
            $totalPagado = $order->payments->sum('amount');
            $totalGeneral = $totalOrden + $totalTaxes;
            // Generar QR
            $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";
            $qrCode = QrCode::create($qrUrl)
                ->setEncoding(new Encoding('UTF-8'))
                ->setSize(250)
                ->setMargin(10);

            $writer = new PngWriter();
            $qrCodeImage = $writer->write($qrCode);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());
            // Renderizar PDF
            // $pdfContent = view('orderPdf', compact(
            $pdfContent = view('fiscalOrderPdf', compact(
                'order',
                'totalOrden',
                'totalTaxes',
                'totalGeneral',
                'totalPagado',
                'imageBase64',
                'qrCodeBase64',
                'dollarRate'
            ))->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($pdfContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // $fileName = 'orden-' . $order->id . '.pdf';
            $fileName = 'factura-' . $order->id . '.pdf';
            Storage::disk('public')->put('orders/' . $fileName, $dompdf->output());
            $pdfUrl = asset('storage/orders/' . $fileName);
            

            //NOTA DE ENTREGA PDF
            $pdfContentNota = view('orderPdf', compact(
                'order',
                'totalOrden',
                'totalTaxes',
                'totalGeneral',
                'totalPagado',
                'imageBase64',
                'qrCodeBase64',
                'dollarRate',
                'tienda'
            ))->render();

            $optionsNota = new Options();
            $optionsNota->set('isHtml5ParserEnabled', true);
            $optionsNota->set('isPhpEnabled', true);

            $dompdfNota = new Dompdf($optionsNota);
            $dompdfNota->loadHtml($pdfContentNota);
            $dompdfNota->setPaper('A4', 'portrait');
            $dompdfNota->render();

            // $fileName = 'orden-' . $order->id . '.pdf';
            $fileNameNota = 'NotaEntrega-' . $order->id . '.pdf';
            Storage::disk('public')->put('orders/' . $fileNameNota, $dompdfNota->output());
            $pdfUrlNota = asset('storage/orders/' . $fileNameNota);
        } else {
            $pdfUrl = null;
            $pdfUrlNota = null;
        }

        return response()->json([
            'message' => $approvedPayments->isNotEmpty()
                ? 'Venta registrada exitosamente con pagos aprobados.'
                : 'Venta registrada sin pagos aprobados (PDF no generado).',
                'pdf_url' => $pdfUrl,
                'nota_entrega_pdf_url' => $pdfUrlNota,
                'created_customer_temporary_password' => $createdCustomerTemporaryPassword,
        ], 200);
    }

    private function resolveCustomerRoleIds(): array
    {
        $roleIds = Role::query()
            ->whereIn(DB::raw('LOWER(name)'), ['user', 'cliente', 'customer'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($roleIds)) {
            return [(int) Role::query()->firstOrCreate(['name' => 'user'])->id];
        }

        return $roleIds;
    }

    private function resolveCustomerRoleId(): int
    {
        $roleId = Role::query()
            ->whereIn(DB::raw('LOWER(name)'), ['user', 'cliente', 'customer'])
            ->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        return (int) Role::query()->firstOrCreate(['name' => 'user'])->id;
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
    
        $customer = User::find($customerId);
        $tenantForSale = Tenant::find(optional($customer)->tenant_id);
        if (!$tenantForSale) {
            return response()->json(['error' => 'No se encontró la tienda asociada al cliente.'], 422);
        }

        $deliveryType = strtolower(trim((string) $request->input('delivery_type', '')));
        if (!in_array($deliveryType, ['pickup', 'shipping'], true)) {
            $deliveryType = strtolower(trim((string) $preference)) === 'envío' ? 'shipping' : 'pickup';
        }

        if ($deliveryType === 'shipping' && empty(trim((string) $address))) {
            return response()->json(['error' => 'La dirección es obligatoria para entregas por envío.'], 422);
        }

        if ($deliveryType === 'shipping' && (bool) ($tenantForSale->restrict_delivery_city_to_tenant ?? true)) {
            $deliveryCityId = (int) $request->input('delivery_city_id', 0);
            $shippingCityValidation = $this->validateShippingCityAgainstTenant($tenantForSale, $deliveryCityId);

            if (!($shippingCityValidation['ok'] ?? false)) {
                return response()->json(['error' => (string) ($shippingCityValidation['message'] ?? 'Solo se permiten envíos a la ciudad de la tienda.')], 422);
            }
        }

        $saleCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenantForSale);

        // Crear orden de venta con status en 0 (pendiente)
        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'date' => now()->toDateString(),
            'status' => 0, // Pendiente por defecto en eCommerce
            'address' => $address ?? 'Tienda',
            'preference' => $preference,
            'sale_currency_code' => $saleCurrencyCode,
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
                        $path = ImageStorage::storeUploadedFile($image, 'payment_images');
                        
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

        $isSeller = $user?->hasStoreRole('seller') ?? false;
        $isWarehouse = $user?->hasStoreRole('warehouse') ?? false;
        $canApprovePayments = !$isWarehouse;
        $canDeliverOrders = $isSeller || $isWarehouse || ($user?->isAdmin() ?? false) || ($user?->isOwner() ?? false);
        $pageTitle = 'VENTAS REALIZADAS';
        $isPendingDeliveryView = false;
    
        return view('salesOrders', compact('salesOrders', 'canApprovePayments', 'canDeliverOrders', 'pageTitle', 'isPendingDeliveryView'));
    }

    public function viewPendingDeliveryOrders()
    {
        $user = auth()->user();

        $salesOrders = SalesOrder::with(['user', 'details', 'details.variant', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->where('deliver_status', 0)
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhereHas('payments', function ($paymentQuery) {
                        $paymentQuery->where('status', 1);
                    });
            })
            ->orderBy('id', 'desc')
            ->get();

        foreach ($salesOrders as $order) {
            $order->total_items = $order->details->sum('quantity');
        }

        $isSeller = $user?->hasStoreRole('seller') ?? false;
        $isWarehouse = $user?->hasStoreRole('warehouse') ?? false;
        $canApprovePayments = !$isWarehouse;
        $canDeliverOrders = $isSeller || $isWarehouse || ($user?->isAdmin() ?? false) || ($user?->isOwner() ?? false);
        $pageTitle = 'PEDIDOS PENDIENTES DE ENTREGA';
        $isPendingDeliveryView = true;

        return view('salesOrders', compact('salesOrders', 'canApprovePayments', 'canDeliverOrders', 'pageTitle', 'isPendingDeliveryView'));
    }

    public function viewReceivables()
    {
        $user = auth()->user();

        $salesOrders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->where('status', '!=', 2)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->total_items = (int) $order->details->sum('quantity');
                $order->order_total_amount = (float) $order->details->sum('amount');
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount, 2));

                return $order;
            })
            ->filter(fn (SalesOrder $order) => $order->pending_amount > 0)
            ->values();

        $totalReceivable = (float) $salesOrders->sum('pending_amount');
        $ordersCount = (int) $salesOrders->count();

        return view('accountsReceivable', compact('salesOrders', 'totalReceivable', 'ordersCount'));
    }

    public function viewPaidPendingDelivery()
    {
        $user = auth()->user();

        $salesOrders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->where('deliver_status', 0)
            ->where('status', '!=', 2)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->total_items = (int) $order->details->sum('quantity');
                $order->order_total_amount = (float) $order->details->sum('amount');
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount, 2));

                return $order;
            })
            ->filter(fn (SalesOrder $order) => $order->pending_amount <= 0.0001)
            ->values();

        $ordersCount = (int) $salesOrders->count();
        $totalPaidOrdersAmount = (float) $salesOrders->sum('approved_paid_amount');

        return view('paidPendingDeliveries', compact('salesOrders', 'ordersCount', 'totalPaidOrdersAmount'));
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

    public function viewMyOrders(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $salesOrders = SalesOrder::with(['details', 'payments.payment', 'tenant'])
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($order) {
                $total = (float) $order->details->sum('amount');

                return [
                    'id' => $order->id,
                    'date' => $order->date,
                    'status' => (int) $order->status,
                    'deliver_status' => (int) ($order->deliver_status ?? 0),
                    'document_issue_mode' => (string) ($order->document_issue_mode ?? 'delivery_note'),
                    'preference' => $order->preference,
                    'address' => $order->address,
                    'tenant_name' => $order->tenant->name ?? 'Tienda',
                    'items_count' => (int) $order->details->sum('quantity'),
                    'total' => round($total, 2),
                    'public_url' => url('/publicOrder/' . $order->id),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'orders' => $salesOrders,
        ]);
    }


    public function showByOrder($id)
    {
        $order = SalesOrder::with(['user', 'tenant', 'details', 'details.variant','details.variant.product', 'payments', 'payments.payment', 'payments.images', 'electronicDocuments'])->find($id);
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
        $order->latest_electronic_document = $order->electronicDocuments->sortByDesc('id')->first();
        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $orderCurrencySymbol = TenantCurrency::resolveCurrencySymbol($orderCurrencyCode);

        return view('salesOrderDetail', compact('order', 'totalOrden', 'totalPagado', 'orderCurrencyCode', 'orderCurrencySymbol'));
    }
    public function showPublicOrder($id)
    {
        $order = SalesOrder::with(['user', 'tenant', 'details', 'details.variant','details.variant.product', 'payments', 'payments.payment', 'payments.images'])->find($id);
        // Calcular el total de la orden
        $totalOrden = $order->details->sum(function ($detalle) {
            return $detalle->amount;
        });
        // Calcular el total pagado
        $totalPagado = $order->payments->sum(function ($payment) {
            return $payment->amount;
        });

        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $orderCurrencySymbol = TenantCurrency::resolveCurrencySymbol($orderCurrencyCode);

        return view('orderInfoQr', compact('order', 'totalOrden', 'totalPagado', 'orderCurrencyCode', 'orderCurrencySymbol'));
    }

    private function resolveOrderCurrencyCode(SalesOrder $order): string
    {
        $paymentCurrencyCodes = $order->payments
            ->map(function (Payment $payment) {
                $raw = strtoupper(trim((string) ($payment->currency ?? $payment->payment?->currency?->code ?? '')));

                if (in_array($raw, ['USD', 'EUR'], true)) {
                    return $raw;
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($paymentCurrencyCodes->count() === 1) {
            return (string) $paymentCurrencyCodes->first();
        }

        $stored = strtoupper(trim((string) ($order->sale_currency_code ?? '')));
        if (in_array($stored, ['USD', 'EUR'], true)) {
            return $stored;
        }

        return TenantCurrency::resolveBaseCurrencyCode($order->tenant);
    }

    public function downloadStoredPdf(Request $request, int $id, string $type)
    {
        $order = SalesOrder::with(['user', 'details', 'details.variant.product', 'details.taxes', 'payments.payment'])->findOrFail($id);

        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $emissionCurrencyCode = $this->resolveEmissionCurrencyCode((string) $request->query('currency_code', ''), $orderCurrencyCode);

        if ($request->has('currency_code')) {
            return $this->downloadRenderedPdfByCurrency($order, $type, $emissionCurrencyCode);
        }

        $assets = $this->ensureAssociatedPdfAssets($order);
        $filePath = $type === 'delivery' ? $assets['delivery_path'] : $assets['invoice_path'];
        $fileName = basename($filePath);

        return response()->download($filePath, $fileName, ['Content-Type' => 'application/pdf']);
    }

    private function ensureAssociatedPdfAssets(SalesOrder $order): array
    {
        $invoiceRelative = 'orders/factura-' . $order->id . '.pdf';
        $deliveryRelative = 'orders/NotaEntrega-' . $order->id . '.pdf';

        if (!Storage::disk('public')->exists($invoiceRelative) || !Storage::disk('public')->exists($deliveryRelative)) {
            $this->generateAssociatedPdfAssets($order);
        }

        return [
            'invoice_path' => storage_path('app/public/' . $invoiceRelative),
            'delivery_path' => storage_path('app/public/' . $deliveryRelative),
            'invoice_url' => asset('storage/' . $invoiceRelative),
            'delivery_url' => asset('storage/' . $deliveryRelative),
        ];
    }

    private function generateAssociatedPdfAssets(SalesOrder $order): array
    {
        $order->loadMissing(['details.taxes', 'details.variant.product', 'payments.payment', 'tenant']);
        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $pdfCurrencyContext = $this->buildPdfCurrencyContext($order, $orderCurrencyCode);

        $serverIp = request()->getHost();
        $imagePath = storage_path('app/public/products/infblack.png');
        $imageData = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : '';
        $imageBase64 = $imageData !== '' ? 'data:image/png;base64,' . $imageData : null;

        $totalOrden = (float) $order->details->sum('amount');
        $totalTaxes = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalPagado = (float) $order->payments->sum('amount');
        $totalGeneral = $totalOrden + $totalTaxes;
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $order->tenant_id)->first();
        $tienda = $order->tenant;

        $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";
        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);

        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());

        $invoiceHtml = view('fiscalOrderPdf', compact(
            'order',
            'totalOrden',
            'totalTaxes',
            'totalGeneral',
            'totalPagado',
            'imageBase64',
            'qrCodeBase64',
            'dollarRate'
        ))->with($pdfCurrencyContext)->render();

        $deliveryHtml = view('orderPdf', compact(
            'order',
            'totalOrden',
            'totalTaxes',
            'totalGeneral',
            'totalPagado',
            'imageBase64',
            'qrCodeBase64',
            'dollarRate',
            'tienda'
        ))->with($pdfCurrencyContext)->render();

        $invoiceOutput = $this->renderPdfOutput($invoiceHtml);
        $deliveryOutput = $this->renderPdfOutput($deliveryHtml);

        $invoiceRelative = 'orders/factura-' . $order->id . '.pdf';
        $deliveryRelative = 'orders/NotaEntrega-' . $order->id . '.pdf';

        Storage::disk('public')->put($invoiceRelative, $invoiceOutput);
        Storage::disk('public')->put($deliveryRelative, $deliveryOutput);

        return [
            'invoice_path' => storage_path('app/public/' . $invoiceRelative),
            'delivery_path' => storage_path('app/public/' . $deliveryRelative),
            'invoice_url' => asset('storage/' . $invoiceRelative),
            'delivery_url' => asset('storage/' . $deliveryRelative),
        ];
    }

    private function renderPdfOutput(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function downloadRenderedPdfByCurrency(SalesOrder $order, string $type, string $emissionCurrencyCode)
    {
        $order->loadMissing(['details.taxes', 'details.variant.product', 'payments.payment', 'tenant']);

        $serverIp = request()->getHost();
        $imagePath = storage_path('app/public/products/infblack.png');
        $imageData = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : '';
        $imageBase64 = $imageData !== '' ? 'data:image/png;base64,' . $imageData : null;

        $totalOrden = (float) $order->details->sum('amount');
        $totalTaxes = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalPagado = (float) $order->payments->sum('amount');
        $totalGeneral = $totalOrden + $totalTaxes;
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $order->tenant_id)->first();
        $tienda = $order->tenant;
        $pdfCurrencyContext = $this->buildPdfCurrencyContext($order, $emissionCurrencyCode);

        $qrUrl = "http://{$serverIp}:8000/publicOrder/{$order->id}";
        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);

        $writer = new PngWriter();
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($writer->write($qrCode)->getString());

        $viewName = $type === 'delivery' ? 'orderPdf' : 'fiscalOrderPdf';
        $html = view($viewName, compact(
            'order',
            'totalOrden',
            'totalTaxes',
            'totalGeneral',
            'totalPagado',
            'imageBase64',
            'qrCodeBase64',
            'dollarRate',
            'tienda'
        ))->with($pdfCurrencyContext)->render();

        $output = $this->renderPdfOutput($html);
        $prefix = $type === 'delivery' ? 'NotaEntrega' : 'factura';
        $fileName = $prefix . '-' . $order->id . '-' . strtolower($emissionCurrencyCode) . '.pdf';

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function resolveEmissionCurrencyCode(string $requestedCode, string $fallbackCode): string
    {
        $normalized = strtoupper(trim($requestedCode));

        if (in_array($normalized, ['BS', 'VES'], true)) {
            return 'VES';
        }

        if (in_array($normalized, ['USD', 'EUR'], true)) {
            return $normalized;
        }

        return $fallbackCode;
    }

    private function buildPdfCurrencyContext(SalesOrder $order, string $emissionCurrencyCode): array
    {
        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $emissionCurrencyCode = $this->resolveEmissionCurrencyCode($emissionCurrencyCode, $orderCurrencyCode);

        $dollarRate = (float) (DollarRate::latest('created_at')->where('tenant_id', $order->tenant_id)->value('rate') ?? 0);
        $euroRate = (float) (EuroRate::latest('created_at')->where('tenant_id', $order->tenant_id)->value('rate') ?? 0);

        $orderToBs = $this->resolveCurrencyToBsRate($orderCurrencyCode, $dollarRate, $euroRate);
        $emissionToBs = $this->resolveCurrencyToBsRate($emissionCurrencyCode, $dollarRate, $euroRate);

        $conversionFactor = 1.0;
        if ($orderCurrencyCode !== $emissionCurrencyCode) {
            if ($emissionCurrencyCode === 'VES') {
                $conversionFactor = $orderToBs > 0 ? $orderToBs : 1.0;
            } elseif ($orderCurrencyCode === 'VES') {
                $conversionFactor = $emissionToBs > 0 ? (1 / $emissionToBs) : 1.0;
            } else {
                $conversionFactor = ($orderToBs > 0 && $emissionToBs > 0) ? ($orderToBs / $emissionToBs) : 1.0;
            }
        }

        return [
            'orderCurrencyCode' => $orderCurrencyCode,
            'emissionCurrencyCode' => $emissionCurrencyCode,
            'emissionCurrencySymbol' => $this->resolveCurrencySymbolForDisplay($emissionCurrencyCode),
            'emissionConversionFactor' => $conversionFactor,
            'emissionRateToBs' => $emissionToBs,
        ];
    }

    private function resolveCurrencyToBsRate(string $currencyCode, float $dollarRate, float $euroRate): float
    {
        $normalized = strtoupper(trim($currencyCode));

        if (in_array($normalized, ['VES', 'BS'], true)) {
            return 1.0;
        }

        if ($normalized === 'EUR') {
            return $euroRate > 0 ? $euroRate : 0;
        }

        if ($normalized === 'USD') {
            return $dollarRate > 0 ? $dollarRate : 0;
        }

        return 0;
    }

    private function resolveCurrencySymbolForDisplay(string $currencyCode): string
    {
        $normalized = strtoupper(trim($currencyCode));

        if ($normalized === 'EUR') {
            return '€';
        }

        if ($normalized === 'USD') {
            return '$';
        }

        if (in_array($normalized, ['VES', 'BS'], true)) {
            return 'Bs';
        }

        return '';
    }

    public function getPaymentMethods()
    {
        $user = auth()->user();
        $paymentMethods = PaymentMethod::with('currency')
            ->when($user?->tenant_id, function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->active()
            ->get();
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
            ->active()
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

    private function getVariantDiscountedUnitPrice(ProductVariant $variant): float
    {
        $basePrice = (float) ($variant->price ?? 0);
        $productDiscount = max(0, min(100, (float) ($variant->product->discount_percentage ?? 0)));
        $variantDiscount = max(0, min(100, (float) ($variant->discount_percentage ?? 0)));

        $afterProductDiscount = $basePrice * ((100 - $productDiscount) / 100);

        return round($afterProductDiscount * ((100 - $variantDiscount) / 100), 2);
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

    public function resolveScanCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:150',
        ]);

        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);
        $code = trim((string) $request->input('code'));

        $variant = ProductVariant::with(['product.taxes'])
            ->where(function ($query) use ($code) {
                $query->where('qr_code', $code)->orWhere('barcode', $code);
            })
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->first();

        if ($variant) {
            $price = $this->getVariantDiscountedUnitPrice($variant);

            return response()->json([
                'success' => true,
                'type' => 'variant',
                'variant' => [
                    'id' => $variant->id,
                    'product_name' => $variant->product->name ?? 'Producto',
                    'size' => $variant->size,
                    'stock' => (float) $variant->stock,
                    'price' => $price,
                    'taxes' => ($variant->product->taxes ?? collect())->map(function ($tax) {
                        return [
                            'name' => $tax->name,
                            'rate' => (float) $tax->rate,
                        ];
                    })->values(),
                ],
            ]);
        }

        $package = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.taxes'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('qr_code', $code)->orWhere('barcode', $code);
            })
            ->first();

        if ($package) {
            return response()->json([
                'success' => true,
                'type' => 'package',
                'package_id' => $package->id,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró una variante o paquete con ese código.',
        ], 404);
    }

    public function orderToggleStatus($id, Request $request)
    {
        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['message' => 'No autorizado para cambiar el estado de la orden.'], 403);
        }

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

        WorkflowNotifier::notifyUser($order->user, [
            'title' => 'Estado de pedido actualizado',
            'message' => 'Tu pedido #' . $order->id . ' cambió de estado.',
            'type' => 'order-status',
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'action' => 'order_status_updated',
        ]);

        if ((int) $order->status === 1) {
            WorkflowNotifier::notifyTenantRoles((int) $order->tenant_id, ['almacen'], [
                'title' => 'Pedido listo para entrega',
                'message' => 'El pedido #' . $order->id . ' fue aprobado y está listo para gestionar entrega.',
                'type' => 'delivery-pending',
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'action' => 'prepare_delivery',
            ]);

            $this->attemptElectronicEmission($order);
        }
    
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
            // Enviar notificación al cliente con el PDF
            if (!empty($order->user?->email)) {
                try {
                    Mail::to($order->user->email)->send(new OrderPdfMail($order, $filePath));
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar correo de aprobación de orden', [
                        'order_id' => $order->id,
                        'email' => $order->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
    
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

    public function updateDocumentMode(SalesOrder $order, Request $request)
    {
        $user = auth()->user();
        if ((int) ($order->tenant_id ?? 0) !== (int) ($user->tenant_id ?? 0)) {
            abort(404);
        }

        $validated = $request->validate([
            'document_issue_mode' => 'required|in:delivery_note,electronic_invoice',
        ]);

        $tenantElectronicEnabled = (bool) ($order->tenant?->electronic_invoicing_enabled ?? false);
        if ($validated['document_issue_mode'] === 'electronic_invoice' && !$tenantElectronicEnabled) {
            return back()->with('error', 'La facturación digital está desactivada para esta tienda.');
        }

        $order->document_issue_mode = $validated['document_issue_mode'];
        $order->save();

        if ($order->document_issue_mode === 'electronic_invoice') {
            $this->attemptElectronicEmission($order);
        }

        return back()->with('success', 'Tipo de documento de la venta actualizado.');
    }

    public function orderDeliverToggleStatus($id, Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'warehouse')) {
            return response()->json(['message' => 'No autorizado para cambiar el estado de entrega.'], 403);
        }

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

        WorkflowNotifier::notifyUser($order->user, [
            'title' => 'Actualización de entrega',
            'message' => 'Tu pedido #' . $order->id . ' cambió su estado de entrega.',
            'type' => 'delivery-status',
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'action' => 'delivery_status_updated',
        ]);
    
        // Si la entrega está marcada como realizada, enviar correo de confirmación
        if ((int) $order->deliver_status === 1) {
            if (!empty($order->user?->email)) {
                try {
                    Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar correo de entrega', [
                        'order_id' => $order->id,
                        'email' => $order->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        
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
        DB::raw("SET @user_id = " . auth()->id());

        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['message' => 'No autorizado para cambiar el estado de pagos.'], 403);
        }

        // Buscar el pago
        $payment = Payment::findOrFail($id);
        // Cambiar el estado del pago
        $payment->status = $request->status;
        $payment->save();
        $payment->loadMissing(['salesOrder.user']);

        if ($payment->salesOrder) {
            $orderId = $payment->salesOrder->id;
            $tenantId = (int) ($payment->salesOrder->tenant_id ?? 0);

            WorkflowNotifier::notifyTenantRoles($tenantId, ['administrador', 'admin', 'vendedor'], [
                'title' => 'Pago actualizado',
                'message' => 'El pago #' . $payment->id . ' del pedido #' . $orderId . ' cambió de estado.',
                'type' => 'payment-status',
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'action' => 'payment_status_updated',
            ]);

            if ((int) $payment->status === 1) {
                WorkflowNotifier::notifyUser($payment->salesOrder->user, [
                    'title' => 'Pago aprobado',
                    'message' => 'Tu pago del pedido #' . $orderId . ' fue aprobado.',
                    'type' => 'payment-approved',
                    'tenant_id' => $tenantId,
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'action' => 'payment_approved',
                ]);

                WorkflowNotifier::notifyTenantRoles($tenantId, ['almacen'], [
                    'title' => 'Pedido por entregar',
                    'message' => 'El pedido #' . $orderId . ' tiene pago aprobado. Proceder con entrega.',
                    'type' => 'delivery-pending',
                    'tenant_id' => $tenantId,
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'action' => 'deliver_order',
                ]);
            }
        }
        // Enviar correo de confirmación si el pago es aprobado
        if ($payment->status == 1) {
            $userEmail = $payment->salesOrder?->user?->email;

            if ($payment->salesOrder) {
                $this->attemptElectronicEmission($payment->salesOrder);
            }

            if (!empty($userEmail)) {
                try {
                    Mail::to($userEmail)->send(new PaymentConfirmationMail($payment));
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar correo de aprobación de pago', [
                        'payment_id' => $payment->id,
                        'email' => $userEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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

    private function attemptElectronicEmission(SalesOrder $order): void
    {
        try {
            $order->loadMissing(['payments', 'details', 'details.variant.product', 'details.taxes', 'tenant', 'user']);

            if (($order->document_issue_mode ?? 'delivery_note') !== 'electronic_invoice') {
                return;
            }

            if (!(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)) {
                return;
            }

            $alreadyIssued = ElectronicDocument::query()
                ->where('sales_order_id', $order->id)
                ->whereNotNull('issued_at')
                ->where('is_annulled', false)
                ->exists();

            if ($alreadyIssued) {
                return;
            }

            $hasApprovedPayments = $order->payments->contains(fn (Payment $payment) => (int) $payment->status === 1);
            if (!$hasApprovedPayments && (int) $order->status !== 1) {
                return;
            }

            $service = app(TheFactoryHkaService::class);
            if (!$service->isConfigured()) {
                return;
            }

            $payload = $service->buildInvoicePayloadFromOrder($order);
            $response = $service->emitDocument($payload);

            ElectronicDocument::create([
                'tenant_id' => $order->tenant_id,
                'sales_order_id' => $order->id,
                'created_by' => auth()->id(),
                'provider' => 'thefactoryhka',
                'tipo_documento' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.tipoDocumento', '01'),
                'serie' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.serie', ''),
                'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', Arr::get($payload, 'encabezado.identificacionDocumento.numeroDocumento')),
                'numero_control' => (string) Arr::get($response, 'data.resultado.numeroControl', ''),
                'transaccion_id' => (string) Arr::get($response, 'data.resultado.transaccionId', Arr::get($payload, 'encabezado.identificacionDocumento.transaccionId')),
                'estado_documento' => (string) Arr::get($response, 'data.resultado.autorizado', Arr::get($response, 'data.resultado.imprentaDigital', '')),
                'codigo' => (string) Arr::get($response, 'data.codigo', ''),
                'mensaje' => (string) ($response['message'] ?? ''),
                'url_consulta' => (string) Arr::get($response, 'data.resultado.urlConsulta', ''),
                'request_payload' => $payload,
                'response_payload' => $response['data'] ?? null,
                'issued_at' => ($response['ok'] ?? false) ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo emitir factura electrónica automáticamente', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validateShippingCityAgainstTenant(Tenant $tenant, int $deliveryCityId): array
    {
        if ($deliveryCityId <= 0) {
            return [
                'ok' => false,
                'message' => 'Debes seleccionar la ciudad de entrega.',
            ];
        }

        $tenantCityId = $this->resolveTenantCityId($tenant);
        if ($tenantCityId <= 0) {
            return [
                'ok' => false,
                'message' => 'La tienda no tiene una ciudad configurada para envíos.',
            ];
        }

        if ($tenantCityId !== $deliveryCityId) {
            $tenantCityName = City::query()->whereKey($tenantCityId)->value('name');

            return [
                'ok' => false,
                'message' => 'Solo se permiten envíos para la ciudad de la tienda' . (!empty($tenantCityName) ? ': ' . $tenantCityName : '.') ,
            ];
        }

        return ['ok' => true];
    }

    private function resolveTenantCityId(Tenant $tenant): int
    {
        $rawCity = trim((string) ($tenant->city ?? ''));
        if ($rawCity === '') {
            return 0;
        }

        if (ctype_digit($rawCity)) {
            return (int) $rawCity;
        }

        return (int) (City::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($rawCity)])
            ->value('id') ?? 0);
    }

    public function processReturn(Request $request, $orderId)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['message' => 'No autorizado para registrar devoluciones.'], 403);
        }

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
