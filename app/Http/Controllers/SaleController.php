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
use App\Models\Appointment;
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
use App\Models\SellerCommission;
use App\Events\DeliveryAssignmentUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Mail\PendingDispatchGuidesReportMail;
use App\Support\WorkflowNotifier;
use App\Support\ImageStorage;
use App\Support\TenantCurrency;
use App\Support\ActionReason;
use App\Support\DeliveryManager;
use App\Support\PdfDownload;
use App\Support\TenantPlanCapabilities;
use App\Services\FiscalCorrelativeService;
use App\Services\TheFactoryHkaService;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $customerId = $user;
        $search = trim((string) request()->input('q', ''));
        $searchNormalized = mb_strtolower($search);

        $productItemsQuery = Product::query()
            ->with(['category', 'images', 'variants', 'taxes'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_consumable', false)
            ->where('is_active', true);

        if ($search !== '') {
            $productItemsQuery->where(function ($query) use ($searchNormalized) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchNormalized . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchNormalized . '%'])
                    ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $searchNormalized . '%'])
                    ->orWhereRaw('LOWER(qr_code) LIKE ?', ['%' . $searchNormalized . '%'])
                    ->orWhereHas('variants', function ($variantQuery) use ($searchNormalized) {
                        $variantQuery->whereRaw('LOWER(size) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(qr_code) LIKE ?', ['%' . $searchNormalized . '%']);
                    });
            });
        }

        // Traer todos los productos con sus variantes
        $productItems = $productItemsQuery
        ->orderBy('created_at', 'desc')
        ->paginate(24)
        ->withQueryString();

        $materialPackagesQuery = MaterialPackage::query()
            ->with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images', 'items.variant.product.taxes', 'items.variant.product.variants'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true);

        if ($search !== '') {
            $materialPackagesQuery->where(function ($query) use ($searchNormalized) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchNormalized . '%'])
                    ->orWhereHas('items.variant.product', function ($productQuery) use ($searchNormalized) {
                        $productQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(qr_code) LIKE ?', ['%' . $searchNormalized . '%']);
                    })
                    ->orWhereHas('items.variant', function ($variantQuery) use ($searchNormalized) {
                        $variantQuery->whereRaw('LOWER(size) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $searchNormalized . '%'])
                            ->orWhereRaw('LOWER(qr_code) LIKE ?', ['%' . $searchNormalized . '%']);
                    });
            });
        }

        $materialPackages = $materialPackagesQuery
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
        $tenantPlanCapabilities = TenantPlanCapabilities::forTenant($tenant);
        if ($tenant && !$tenantPlanCapabilities->allowsDeliveryOperations()) {
            $tenant->delivery_enabled = false;
            $tenant->delivery_notifications_enabled = false;
            $tenant->restrict_delivery_city_to_tenant = true;
        }
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

        $existingCustomersForSale = $this->customerCandidatesQuery((int) $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone_number', 'dni']);

        if ($categories->isEmpty()) {
            return redirect()->route('categories.index')
                ->with('warning', 'Debes crear al menos una categoría antes de registrar ventas.');
        }

        return view('sales', compact('categories', 'paymentMethods', 'productItems', 'materialPackages', 'dollarRate', 'euroRate', 'customerId', 'taxes', 'tenant', 'baseCurrencyCode', 'baseCurrencySymbol', 'baseRateToBs', 'ratePayload', 'existingCustomersForSale', 'search'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_type' => 'nullable|in:pickup,delivery,shipping',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_receiver_name' => 'nullable|string|max:120',
            'delivery_receiver_phone' => 'nullable|string|max:30',
            'delivery_city_id' => 'nullable|integer|exists:cities,id',
            'delivery_distance_km' => 'nullable|numeric|min:0',
            'global_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'global_discount_amount' => 'nullable|numeric|min:0',
            'sale_document_mode' => 'nullable|in:delivery_note,electronic_invoice',
            'create_new_customer' => 'nullable|boolean',
            'customer_existing_id' => 'nullable|integer|required_unless:create_new_customer,1',
            'customer_new' => 'nullable|array',
            'customer_new.name' => 'required_if:create_new_customer,1|string|max:255',
            'customer_new.email' => 'required_if:create_new_customer,1|email|unique:users,email',
            'customer_new.phone_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'customer_new.phone_number' => 'required_if:create_new_customer,1|string|max:20',
            'customer_new.dni' => 'required_if:create_new_customer,1|string|max:100',
            'customer_new.is_retention_agent' => 'nullable|boolean',
            'payments' => 'nullable|array',
            'payments.*.methodId' => 'required_with:payments|integer',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.amount_base' => 'nullable|numeric|min:0.01',
            'payments.*.amount_original' => 'nullable|numeric|min:0.01',
            'payments.*.exchange_rate_to_base' => 'nullable|numeric|min:0',
            'payments.*.applies_igtf' => 'nullable|boolean',
            'payments.*.currency' => 'nullable|string|max:10',
            'payments.*.reference' => 'nullable|string|max:255',
            'payments.*.proof_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'mark_delivered' => 'nullable|boolean',
            'mark_payments_paid' => 'nullable|boolean',
            'mark_sale_completed' => 'nullable|boolean',
            'change_paid_in_bs' => 'nullable|boolean',
            'change_rate_to_bs' => 'nullable|numeric|min:0',
            'dollarRate' => 'nullable|numeric|min:0',
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
                'email' => trim((string) ($customerPayload['email'] ?? '')) ?: null,
                'phone_number' => $this->normalizeCustomerPhone(
                    (string) ($customerPayload['phone_number'] ?? ''),
                    $customerPayload['phone_code'] ?? null
                ),
                'dni' => trim((string) ($customerPayload['dni'] ?? '')),
                'is_retention_agent' => (bool) ($customerPayload['is_retention_agent'] ?? false),
                'password' => Hash::make($defaultCustomerPassword),
                'tenant_id' => $tenantId,
                'role_id' => $customerRoleId,
                'is_active' => 1,
            ]);

            $customerId = (int) $createdCustomer->id;
            $createdCustomerTemporaryPassword = $defaultCustomerPassword;
        } else {
            $selectedCustomerId = (int) ($validated['customer_existing_id'] ?? 0);

            $existingCustomer = $this->customerCandidatesQuery($tenantId)
                ->whereKey($selectedCustomerId)
                ->first();

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
        $deliveryReceiverName = trim((string) ($validated['delivery_receiver_name'] ?? ''));
        $deliveryReceiverPhone = trim((string) ($validated['delivery_receiver_phone'] ?? ''));
        $markDelivered = (bool) ($validated['mark_delivered'] ?? false);
        $markPaymentsPaid = (bool) ($validated['mark_payments_paid'] ?? false);
        $markSaleCompleted = (bool) ($validated['mark_sale_completed'] ?? false);
        $changePaidInBs = (bool) ($validated['change_paid_in_bs'] ?? false);
        $requestedDocumentMode = (string) ($validated['sale_document_mode'] ?? 'delivery_note');
        $deliveryDistanceKm = isset($validated['delivery_distance_km']) ? (float) $validated['delivery_distance_km'] : null;

        if (in_array($deliveryType, ['delivery', 'shipping'], true) && $deliveryAddress === '') {
            return response()->json(['error' => 'La dirección es obligatoria para delivery o envío.'], 422);
        }

        if (in_array($deliveryType, ['delivery', 'shipping'], true) && ($deliveryReceiverName === '' || $deliveryReceiverPhone === '')) {
            return response()->json(['error' => 'Debes indicar nombre y teléfono de la persona que recibe para delivery o envío.'], 422);
        }

        $tienda = Tenant::find($tenantId);
        if (!$tienda) {
            return response()->json(['error' => 'No se encontró la tienda asociada a la venta.'], 422);
        }

        if (!TenantPlanCapabilities::forTenant($tienda)->allowsDeliveryOperations() && in_array($deliveryType, ['delivery', 'shipping'], true)) {
            return response()->json(['error' => 'El plan Free no permite usar delivery o envíos en ventas administrativas.'], 403);
        }

        if ($deliveryType === 'delivery' && (bool) ($tienda->restrict_delivery_city_to_tenant ?? true)) {
            $deliveryCityId = (int) ($validated['delivery_city_id'] ?? 0);
            $shippingCityValidation = $this->validateShippingCityAgainstTenant($tienda, $deliveryCityId);

            if (!($shippingCityValidation['ok'] ?? false)) {
                return response()->json(['error' => (string) ($shippingCityValidation['message'] ?? 'Solo se permite delivery en la ciudad de la tienda.')], 422);
            }
        }

        $preference = match ($deliveryType) {
            'delivery' => 'Delivery tienda',
            'shipping' => 'Envío externo',
            default => 'Retiro en tienda',
        };
        $address = $deliveryType !== 'pickup' ? $deliveryAddress : 'Tienda';
        if ($deliveryType !== 'pickup') {
            $address .= "\nRecibe: {$deliveryReceiverName}\nTeléfono receptor: {$deliveryReceiverPhone}";
        }

        if (!$customerId) {
            return response()->json(['error' => 'ID de cliente no válido.'], 400);
        }

        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
        $saleCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tienda);
        $saleRateToBs = max(0, (float) ($validated['dollarRate'] ?? 0));
        if ($saleRateToBs <= 0) {
            $saleRateToBs = (float) TenantCurrency::resolveRateToBs($tenantId, $saleCurrencyCode);
        }

        if ($requestedDocumentMode === 'electronic_invoice' && !(bool) ($tienda?->electronic_invoicing_enabled ?? false)) {
            return response()->json(['error' => 'La facturacion digital esta desactivada para esta tienda.'], 422);
        }

        $documentIssueMode = $requestedDocumentMode === 'electronic_invoice' ? 'electronic_invoice' : 'delivery_note';

        $globalDiscountPercentage = max(0, min(100, (float) ($validated['global_discount_percentage'] ?? 0)));
        $globalDiscountAmountRequested = max(0, (float) ($validated['global_discount_amount'] ?? 0));

        $preparedLines = [];
        $lineSubtotalBeforeDiscountTotal = 0.0;
        $lineSubtotalAfterLineDiscountTotal = 0.0;

        // Preparar renglones y validar descuentos/stock antes de persistir
        foreach ($itemsSelected as $item) {
            $productVariant = ProductVariant::with('product')->find($item['id']);
            if (!$productVariant) {
                return response()->json(['error' => 'Variante no encontrada: ' . $item['id']], 400);
            }

            if ((bool) ($productVariant->product->is_consumable ?? false)) {
                return response()->json(['error' => 'Los productos consumibles no están disponibles para venta directa.'], 422);
            }

            $rawQuantity = (float) ($item['quantity'] ?? 0);
            $quantityInputMode = ProductVariant::normalizeQuantityInputMode($productVariant->quantity_input_mode ?? null);
            $minSaleQuantity = ProductVariant::normalizeMinSaleQuantity($productVariant->min_sale_quantity ?? 1, $quantityInputMode);
            $quantity = $quantityInputMode === 'decimal'
                ? round($rawQuantity, 2)
                : (float) round($rawQuantity);

            if ($quantityInputMode === 'integer' && abs($rawQuantity - round($rawQuantity)) > 0.00001) {
                return response()->json(['error' => 'La variante ' . $productVariant->size . ' solo permite cantidades enteras.'], 422);
            }

            if ($quantityInputMode === 'decimal' && abs($rawQuantity - round($rawQuantity, 2)) > 0.00001) {
                return response()->json(['error' => 'La variante ' . $productVariant->size . ' solo permite hasta 2 decimales en la cantidad.'], 422);
            }

            if ($quantity < $minSaleQuantity) {
                return response()->json([
                    'error' => 'La variante ' . $productVariant->size . ' tiene una venta minima de ' . number_format($minSaleQuantity, 2, '.', '') . '.',
                ], 422);
            }

            if (in_array($deliveryType, ['delivery', 'shipping'], true) && abs($quantity - 1.0) > 0.00001) {
                return response()->json(['error' => 'Las ventas con delivery/envío solo permiten cantidad exacta de 1 por ítem.'], 422);
            }

            if ($productVariant->stock < $quantity) {
                return response()->json(['error' => 'Stock insuficiente para el producto: ' . $item['id']], 400);
            }

            if ($quantity <= 0) {
                return response()->json(['error' => 'Cantidad inválida para el producto: ' . $item['id']], 422);
            }

            $originalUnitPrice = (float) ($productVariant->price ?? 0);
            $discountedUnitPrice = $this->getVariantDiscountedUnitPrice($productVariant);
            $lineDiscountPercentage = max(0, min(100, (float) ($item['line_discount_percentage'] ?? 0)));
            $lineDiscountAmountRequested = max(0, (float) ($item['line_discount_amount'] ?? 0));

            if (($lineDiscountPercentage > 0 || $lineDiscountAmountRequested > 0 || $globalDiscountPercentage > 0 || $globalDiscountAmountRequested > 0)
                && !((bool) (auth()->user()?->isAdmin() ?? false))) {
                return response()->json(['error' => 'Solo los administradores pueden aplicar descuentos.'], 403);
            }

            $lineSubtotalBeforeDiscount = round($originalUnitPrice * $quantity, 2);
            $lineSubtotalAfterCatalogDiscount = round($discountedUnitPrice * $quantity, 2);
            $lineSubtotalAfterPercentageDiscount = round($lineSubtotalAfterCatalogDiscount * ((100 - $lineDiscountPercentage) / 100), 2);
            $lineDiscountAmountApplied = min($lineDiscountAmountRequested, $lineSubtotalAfterPercentageDiscount);
            $lineSubtotalAfterLineDiscount = round(max(0, $lineSubtotalAfterPercentageDiscount - $lineDiscountAmountApplied), 2);

            $preparedLines[] = [
                'item' => $item,
                'product_variant' => $productVariant,
                'quantity' => $quantity,
                'quantity_input_mode' => $quantityInputMode,
                'taxes' => is_array($item['taxes'] ?? null) ? $item['taxes'] : [],
                'line_subtotal_before_discount' => $lineSubtotalBeforeDiscount,
                'line_subtotal_after_line_discount' => $lineSubtotalAfterLineDiscount,
                'line_discount_amount' => round(max(0, $lineSubtotalBeforeDiscount - $lineSubtotalAfterLineDiscount), 2),
                'global_discount_allocated' => 0.0,
            ];

            $lineSubtotalBeforeDiscountTotal += $lineSubtotalBeforeDiscount;
            $lineSubtotalAfterLineDiscountTotal += $lineSubtotalAfterLineDiscount;
        }

        $globalDiscountAmountApplied = 0.0;
        if ($lineSubtotalAfterLineDiscountTotal > 0) {
            $calculatedByPercentage = round($lineSubtotalAfterLineDiscountTotal * ($globalDiscountPercentage / 100), 2);
            $globalDiscountAmountApplied = $calculatedByPercentage > 0 ? $calculatedByPercentage : round($globalDiscountAmountRequested, 2);
            $globalDiscountAmountApplied = min($globalDiscountAmountApplied, round($lineSubtotalAfterLineDiscountTotal, 2));
        }

        if ($globalDiscountAmountApplied > 0 && !empty($preparedLines)) {
            $remaining = $globalDiscountAmountApplied;
            $totalBaseForProration = round($lineSubtotalAfterLineDiscountTotal, 2);
            $lastIndex = count($preparedLines) - 1;

            foreach ($preparedLines as $index => &$line) {
                if ($index === $lastIndex) {
                    $line['global_discount_allocated'] = round(max(0, $remaining), 2);
                    break;
                }

                $weight = $totalBaseForProration > 0
                    ? ((float) $line['line_subtotal_after_line_discount'] / $totalBaseForProration)
                    : 0;
                $allocated = round($globalDiscountAmountApplied * $weight, 2);
                $allocated = min($allocated, (float) $line['line_subtotal_after_line_discount']);

                $line['global_discount_allocated'] = $allocated;
                $remaining = round($remaining - $allocated, 2);
            }
            unset($line);
        }

        $itemsSubtotal = 0.0;

        // Crear orden de venta
        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'sales_rep_user_id' => $this->resolveSalesRepresentativeId(),
            'date' => now()->toDateString(),
            'status' => $markSaleCompleted ? 1 : 0,
            'address' => $address,
            'preference' => $preference,
            'deliver_status' => $markDelivered ? 1 : 0,
            'tenant_id' => $tenantId,
            'document_issue_mode' => $documentIssueMode,
            'sale_currency_code' => $saleCurrencyCode,
            'sale_rate_to_bs' => $saleRateToBs > 0 ? round($saleRateToBs, 6) : null,
            'subtotal_before_discount' => round($lineSubtotalBeforeDiscountTotal, 2),
            'total_discount' => round(max(0, $lineSubtotalBeforeDiscountTotal - ($lineSubtotalAfterLineDiscountTotal - $globalDiscountAmountApplied)), 2),
        ]);

        // Crear detalles y actualizar stock
        foreach ($preparedLines as $line) {
            $quantity = (int) $line['quantity'];
            $lineSubtotalBeforeDiscount = (float) $line['line_subtotal_before_discount'];
            $lineSubtotalAfterLineDiscount = (float) $line['line_subtotal_after_line_discount'];
            $lineSubtotalFinal = round(max(0, $lineSubtotalAfterLineDiscount - (float) $line['global_discount_allocated']), 2);
            $unitPrice = $quantity > 0 ? round($lineSubtotalFinal / $quantity, 2) : 0.0;
            $lineDiscountAmount = round(max(0, $lineSubtotalBeforeDiscount - $lineSubtotalFinal), 2);

            $salesDetail = SalesOrderDetail::create([
                'sales_order_id' => $salesOrder->id,
                'product_variant_id' => $line['item']['id'],
                'quantity' => $quantity,
                'price' => $unitPrice,
                'amount' => $lineSubtotalFinal,
                'line_subtotal_before_discount' => $lineSubtotalBeforeDiscount,
                'line_discount_amount' => $lineDiscountAmount,
            ]);

            $itemsSubtotal += (float) $salesDetail->amount;

            if (!empty($line['taxes'])) {
                foreach ($line['taxes'] as $tax) {
                    $rate = (float) ($tax['rate'] ?? 0);
                    $base = $lineSubtotalFinal;
                    $amount = round($base * ($rate / 100), 2);

                    $salesDetail->taxes()->create([
                        'tax_name' => $tax['name'] ?? 'IVA',
                        'tax_rate' => $rate,
                        'tax_amount' => $amount,
                    ]);
                }
            }

            $productVariant = $line['product_variant'];
            $productVariant->stock -= $quantity;
            $productVariant->save();
        }

        try {
            $deliveryPricing = DeliveryManager::calculate($tienda, $deliveryType, $itemsSubtotal, $deliveryDistanceKm);
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
        $salesOrder->delivery_fee = $deliveryPricing['fee'];
        $salesOrder->delivery_fee_mode = $deliveryPricing['mode'];
        $salesOrder->delivery_distance_km = $deliveryPricing['distance_km'];
        $salesOrder->save();

        $totalTaxes = (float) $salesOrder->details()->with('taxes')->get()->flatMap->taxes->sum('tax_amount');
        $totalWithoutIgtf = round((float) $salesOrder->gross_total + $totalTaxes, 2);
        $igtfRate = $this->resolveIgtfRate();
        $tenantEligibleForIgtf = (bool) ($tienda->electronic_invoicing_enabled ?? false)
            && (bool) ($tienda->special_taxpayer ?? false)
            && $igtfRate > 0;

        // Crear pagos
        $approvedPayments = collect();
        $approvedPaymentsTotalBase = 0.0;
        $approvedPaymentsIgtfEligibleBase = 0.0;
        if (!empty($paymentDetails) && is_array($paymentDetails)) {
            foreach ($paymentDetails as $index => $paymentDetail) {
                $paymentMethod = PaymentMethod::with('currency')
                    ->where('tenant_id', $tenantId)
                    ->active()
                    ->find($paymentDetail['methodId']);

                if (!$paymentMethod) {
                    return response()->json(['error' => 'Uno de los métodos de pago seleccionados está inactivo o no pertenece a esta tienda.'], 422);
                }

                $requiresReference = $paymentMethod->usesReference();
                $reference = trim((string) ($paymentDetail['reference'] ?? ''));

                if ($requiresReference && $reference === '') {
                    return response()->json(['error' => 'El método de pago ' . $paymentMethod->name . ' requiere una referencia.'], 422);
                }

                if ($paymentMethod->requiresProofImage() && !$request->hasFile("payments.$index.proof_image")) {
                    return response()->json(['error' => 'El método de pago ' . $paymentMethod->name . ' requiere comprobante.'], 422);
                }

                $amountOriginal = round(max(0, (float) ($paymentDetail['amount_original'] ?? $paymentDetail['amount'] ?? 0)), 2);
                $amountBase = round(max(0, (float) ($paymentDetail['amount_base'] ?? $paymentDetail['amount'] ?? 0)), 2);
                $exchangeRateToBase = (float) ($paymentDetail['exchange_rate_to_base'] ?? 0);
                if ($exchangeRateToBase <= 0 && $amountOriginal > 0) {
                    $exchangeRateToBase = round($amountBase / $amountOriginal, 6);
                }

                $paymentCurrencyCode = (string) ($paymentMethod->currency->code ?? $paymentDetail['currency'] ?? $saleCurrencyCode);
                $appliesIgtf = $tenantEligibleForIgtf
                    && $this->isForeignCurrencyForIgtf($paymentCurrencyCode);

                $payment = Payment::create([
                    'sales_order_id' => $salesOrder->id,
                    'payment_method' => $paymentMethod->id,
                    'amount' => $amountBase,
                    'amount_original' => $amountOriginal,
                    'amount_base' => $amountBase,
                    'exchange_rate_to_base' => $exchangeRateToBase > 0 ? $exchangeRateToBase : null,
                    'applies_igtf' => (bool) $appliesIgtf,
                    'currency' => $paymentMethod->currency->code ?? $paymentDetail['currency'],
                    'reference' => $requiresReference ? $reference : null,
                    'status' => $markPaymentsPaid ? 1 : 0,
                ]);

                if ($payment->status == 1) {
                    $approvedPayments->push($payment);
                    $approvedPaymentsTotalBase += $amountBase;
                    if ((bool) $appliesIgtf) {
                        $approvedPaymentsIgtfEligibleBase += $amountBase;
                    }
                }

                if ($request->hasFile("payments.$index.proof_image")) {
                    $proofPath = ImageStorage::storeUploadedImageAsWebp(
                        $request->file("payments.$index.proof_image"),
                        'payment_images'
                    );

                    PaymentImage::create([
                        'payment_id' => $payment->id,
                        'image_path' => $proofPath,
                    ]);
                }
            }
        }

        $shouldApplyIgtf = $tenantEligibleForIgtf;

        $igtfBaseAmount = $shouldApplyIgtf
            ? round(min(max(0, $approvedPaymentsIgtfEligibleBase), max(0, $totalWithoutIgtf)), 2)
            : 0.0;
        $igtfAmount = $shouldApplyIgtf ? round($igtfBaseAmount * ($igtfRate / 100), 2) : 0.0;
        $totalWithIgtf = round($totalWithoutIgtf + $igtfAmount, 2);
        $changeDueBase = round(max(0, $approvedPaymentsTotalBase - $totalWithIgtf), 2);

        if ($markPaymentsPaid && $approvedPaymentsTotalBase + 0.0001 < $totalWithIgtf) {
            return response()->json([
                'error' => 'Los pagos aprobados no cubren el total de la venta incluyendo IGTF.',
                'required_total' => $totalWithIgtf,
                'approved_total' => round($approvedPaymentsTotalBase, 2),
            ], 422);
        }

        $changeRateToBs = max(0, (float) ($validated['change_rate_to_bs'] ?? $dollarRate));
        $changeDueBs = ($changePaidInBs && $changeDueBase > 0 && $changeRateToBs > 0)
            ? round($changeDueBase * $changeRateToBs, 2)
            : 0.0;

        $salesOrder->total_paid_base = round(max(0, $approvedPaymentsTotalBase), 2);
        $salesOrder->igtf_base_amount = $igtfBaseAmount;
        $salesOrder->igtf_amount = $igtfAmount;
        $salesOrder->change_due_base = $changeDueBase;
        $salesOrder->change_paid_in_bs = $changePaidInBs;
        $salesOrder->change_rate_to_bs = $changePaidInBs && $changeRateToBs > 0 ? $changeRateToBs : null;
        $salesOrder->change_due_bs = $changePaidInBs ? $changeDueBs : 0;
        $salesOrder->save();

        // Recuperar la orden con relaciones + taxes
        $order = SalesOrder::with([
            'user',
            'details',
            'details.taxes.tax',
            'details.variant.product',
            'payments.payment',
            'returns.items',
            'salesRepresentative'
        ])->findOrFail($salesOrder->id);

        try {
            $this->syncSellerCommissionForOrder($order);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo sincronizar comision del vendedor para la venta.', [
                'order_id' => (int) $order->id,
                'tenant_id' => (int) $order->tenant_id,
                'error' => $exception->getMessage(),
            ]);
        }

        $creatorName = trim((string) (auth()->user()->name ?? ''));
        $salesRepresentativeName = trim((string) ($order->salesRepresentative->name ?? ''));
        $createdByLabel = $creatorName !== '' ? $creatorName : ($salesRepresentativeName !== '' ? $salesRepresentativeName : 'usuario interno');

        try {
            WorkflowNotifier::notifyTenantRoles((int) $order->tenant_id, ['owner', 'administrador', 'admin', 'vendedor'], [
                'title' => 'Nueva venta registrada',
                'message' => 'Se registró la venta #' . $order->id . ' desde la vista administrativa por ' . $createdByLabel . '.',
                'type' => 'sale-created',
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'action' => 'sale_created_from_admin',
                'meta' => [
                    'source' => 'admin_sales_view',
                    'creator_name' => $creatorName,
                    'sales_representative_name' => $salesRepresentativeName,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo despachar notificaciones de venta interna.', [
                'order_id' => (int) $order->id,
                'tenant_id' => (int) $order->tenant_id,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($markSaleCompleted) {
            try {
                $this->attemptElectronicEmission($order);
            } catch (\Throwable $exception) {
                Log::warning('No se pudo emitir documento electronico al completar la venta.', [
                    'order_id' => (int) $order->id,
                    'tenant_id' => (int) $order->tenant_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

    // =====================================
    //   GENERAR PDF SI HAY PAGOS APROBADOS
    // =====================================
        if ($approvedPayments->isNotEmpty()) {
            try {
                $imageBase64 = $this->resolveTenantBillingLogoDataUri($order->tenant ?? null);

                // Totales
                $totalOrden = (float) $order->gross_total;
                $totalTaxes = $order->details->flatMap->taxes->sum('tax_amount');
                $totalPagado = $order->payments->sum('amount');
                $totalGeneral = $totalOrden + $totalTaxes;
                // Generar QR
                $qrUrl = url('/publicOrder/' . $order->id);
                $qrCode = QrCode::create($qrUrl)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setSize(250)
                    ->setMargin(10);

                $writer = new PngWriter();
                $qrCodeImage = $writer->write($qrCode);
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());
                $pdfUrl = null;
                if (($order->document_issue_mode ?? 'delivery_note') !== 'electronic_invoice') {
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

                    $fileName = 'factura-' . $order->id . '.pdf';
                    Storage::disk('public')->put('orders/' . $fileName, $dompdf->output());
                    $pdfUrl = asset('storage/orders/' . $fileName);
                }


                //ORDEN DE ENTREGA PDF
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
                $fileNameNota = $this->resolveInternalDispatchFilename((int) $order->id);
                Storage::disk('public')->put('orders/' . $fileNameNota, $dompdfNota->output());
                $pdfUrlNota = asset('storage/orders/' . $fileNameNota);
            } catch (\Throwable $exception) {
                Log::warning('No se pudieron generar los PDF de la venta.', [
                    'order_id' => (int) $order->id,
                    'tenant_id' => (int) $order->tenant_id,
                    'error' => $exception->getMessage(),
                ]);
                $pdfUrl = null;
                $pdfUrlNota = null;
            }
        } else {
            $pdfUrl = null;
            $pdfUrlNota = null;
        }

        if ($deliveryType === 'delivery' && $markSaleCompleted) {
            try {
                $this->notifyDeliveryTeamIfEnabled($tienda, $salesOrder, 'Pedido listo para despacho desde venta interna.');
            } catch (\Throwable $exception) {
                Log::warning('No se pudo notificar al equipo de delivery para la venta.', [
                    'order_id' => (int) $salesOrder->id,
                    'tenant_id' => (int) $salesOrder->tenant_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $order->loadMissing('electronicDocuments');
            $hkaDispatchGuide = $this->resolveLatestDispatchGuideDocument($order);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo resolver la guia de despacho HKA de la venta.', [
                'order_id' => (int) $order->id,
                'tenant_id' => (int) $order->tenant_id,
                'error' => $exception->getMessage(),
            ]);
            $hkaDispatchGuide = null;
        }

        return response()->json([
            'message' => $approvedPayments->isNotEmpty()
                ? 'Venta registrada exitosamente con pagos aprobados.'
                : 'Venta registrada sin pagos aprobados (PDF no generado).',
                'pdf_url' => $pdfUrl,
                'nota_entrega_pdf_url' => $pdfUrlNota,
                'hka_dispatch_guide_download_url' => (optional($hkaDispatchGuide)->issued_at)
                    ? route('sales.dispatchGuide.download', [
                        'order' => $order->id,
                        'tipo_archivo' => 'pdf',
                        'disposition' => 'attachment',
                    ])
                    : null,
                'igtf_base_amount' => $salesOrder->igtf_base_amount,
                'igtf_amount' => $salesOrder->igtf_amount,
                'total_paid_base' => $salesOrder->total_paid_base,
                'change_due_base' => $salesOrder->change_due_base,
                'change_due_bs' => $salesOrder->change_due_bs,
                'hka_dispatch_guide_issued' => (bool) optional($hkaDispatchGuide)->issued_at,
                'hka_dispatch_guide_number' => $hkaDispatchGuide?->numero_documento,
                'hka_dispatch_guide_control' => $hkaDispatchGuide?->numero_control,
                'created_customer_temporary_password' => $createdCustomerTemporaryPassword,
        ], 200);
    }

    private function resolveIgtfRate(): float
    {
        $rate = (float) (Tax::query()
            ->whereRaw('(LOWER(name) = ? OR LOWER(name) LIKE ?)', ['igtf', '%igtf%'])
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->value('rate') ?? 0);

        return $rate > 0 ? $rate : 3.0;
    }

    private function paymentMethodAppliesIgtf(PaymentMethod $paymentMethod, string $tenantBaseCurrency): bool
    {
        if (!is_null($paymentMethod->applies_igtf_base)) {
            return (bool) $paymentMethod->applies_igtf_base;
        }

        $methodCurrency = strtoupper(trim((string) ($paymentMethod->currency->code ?? $tenantBaseCurrency)));
        if (!$this->isForeignCurrencyForIgtf($methodCurrency)) {
            return false;
        }

        $name = Str::lower(trim((string) ($paymentMethod->name ?? '')));
        if ($name === '') {
            return true;
        }

        $nonEligibleKeywords = [
            'pago movil', 'pago móvil', 'punto de venta', 'tarjeta', 'credito', 'crédito',
            'debito', 'débito', 'transferencia nacional', 'nacional', 'bs', 'bolivar', 'bolívar',
        ];

        if (Str::contains($name, $nonEligibleKeywords)) {
            return false;
        }

        // In foreign currency, apply IGTF by default unless a non-eligible method is explicitly detected.
        return true;
    }

    private function isForeignCurrencyForIgtf(?string $currencyCode): bool
    {
        $normalized = strtoupper(trim((string) $currencyCode));

        return !in_array($normalized, ['BS', 'BSD', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'], true);
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

    private function normalizeCustomerPhone(string $phoneNumber, mixed $phoneCode = null): ?string
    {
        $rawPhone = trim($phoneNumber);
        if ($rawPhone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($rawPhone, '+')) {
            return '+' . $digits;
        }

        $rawCode = trim((string) ($phoneCode ?? ''));
        $codeDigits = preg_replace('/\D+/', '', $rawCode);
        if ($codeDigits !== '') {
            return '+' . $codeDigits . $digits;
        }

        return $digits;
    }

    private function customerCandidatesQuery(int $tenantId)
    {
        $excludedRoleIds = Role::query()
            ->get()
            ->filter(function (Role $role) {
                $canonicalRole = User::canonicalRoleName($role->name);
                $normalizedName = strtolower(trim((string) $role->name));

                return in_array($canonicalRole, ['owner', 'admin', 'seller', 'warehouse'], true)
                    || in_array($normalizedName, ['super_user', 'super user'], true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->when(!empty($excludedRoleIds), function ($query) use ($excludedRoleIds) {
                $query->where(function ($customerQuery) use ($excludedRoleIds) {
                    $customerQuery->whereNotIn('role_id', $excludedRoleIds)
                        ->orWhereNull('role_id');
                });
            });
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

    public function downloadPdf(Request $request, $id)
    {
        $order = SalesOrder::with([
            'user',
            'details.variant.product',
            'payments.payment'
        ])->findOrFail($id);

        $totalOrden = (float) $order->gross_total;
        $totalPagado = $order->payments->sum('amount');

        $qrUrl = url('/publicOrder/' . $order->id);

        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);
        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());

        $imageBase64 = $this->resolveTenantBillingLogoDataUri($order->tenant ?? null);

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
            ->header('Content-Disposition', PdfDownload::buildDispositionHeader($request, 'orden-' . $order->id . '.pdf'));
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
        $deliveryReceiverName = trim((string) $request->input('delivery_receiver_name', ''));
        $deliveryReceiverPhone = trim((string) $request->input('delivery_receiver_phone', ''));
    
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
            $variant = $item['item'] ?? null; // Accedemos a la información del producto
            $variantId = (int) ($variant['id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($variant['price'] ?? 0);

            if ($variantId <= 0 || $quantity <= 0 || $price <= 0) {
                return response()->json(['error' => 'Uno de los productos enviados es inválido.'], 422);
            }

            $groupedData[] = [
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
                'amount' => $price * $quantity,
            ];
        }
    
        $customer = User::find($customerId);
        $tenantForSale = Tenant::find(optional($customer)->tenant_id);
        if (!$tenantForSale) {
            return response()->json(['error' => 'No se encontró la tienda asociada al cliente.'], 422);
        }

        $deliveryType = strtolower(trim((string) $request->input('delivery_type', '')));
        if (!in_array($deliveryType, ['pickup', 'delivery', 'shipping'], true)) {
            $normalizedPreference = strtolower(trim((string) $preference));
            $deliveryType = match ($normalizedPreference) {
                'delivery', 'delivery tienda', 'envío', 'envio' => 'delivery',
                'envío externo', 'envio externo', 'shipping' => 'shipping',
                default => 'pickup',
            };
        }

        if (!TenantPlanCapabilities::forTenant($tenantForSale)->allowsDeliveryOperations() && in_array($deliveryType, ['delivery', 'shipping'], true)) {
            return response()->json(['error' => 'El plan Free no permite usar delivery o envíos en este flujo de ventas.'], 403);
        }

        if (in_array($deliveryType, ['delivery', 'shipping'], true) && empty(trim((string) $address))) {
            return response()->json(['error' => 'La dirección es obligatoria para delivery o envío.'], 422);
        }

        if (in_array($deliveryType, ['delivery', 'shipping'], true) && ($deliveryReceiverName === '' || $deliveryReceiverPhone === '')) {
            return response()->json(['error' => 'Debes indicar nombre y teléfono de la persona que recibe para delivery o envío.'], 422);
        }

        if ($deliveryType === 'delivery' && (bool) ($tenantForSale->restrict_delivery_city_to_tenant ?? true)) {
            $deliveryCityId = (int) $request->input('delivery_city_id', 0);
            $shippingCityValidation = $this->validateShippingCityAgainstTenant($tenantForSale, $deliveryCityId);

            if (!($shippingCityValidation['ok'] ?? false)) {
                return response()->json(['error' => (string) ($shippingCityValidation['message'] ?? 'Solo se permite delivery en la ciudad de la tienda.')], 422);
            }
        }

        $deliveryDistanceKm = $request->filled('delivery_distance_km')
            ? (float) $request->input('delivery_distance_km')
            : null;

        $saleCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenantForSale);
        $saleRateToBs = (float) TenantCurrency::resolveRateToBs((int) $tenantForSale->id, $saleCurrencyCode);

        // Crear orden de venta con status en 0 (pendiente)
        $formattedAddress = in_array($deliveryType, ['delivery', 'shipping'], true)
            ? trim((string) $address) . "\nRecibe: {$deliveryReceiverName}\nTeléfono receptor: {$deliveryReceiverPhone}"
            : ($address ?? 'Tienda');

        $salesOrder = SalesOrder::create([
            'user_id' => $customerId,
            'sales_rep_user_id' => $this->resolveSalesRepresentativeId(),
            'date' => now()->toDateString(),
            'status' => 0, // Pendiente por defecto en eCommerce
            'address' => $formattedAddress,
            'preference' => $preference,
            'tenant_id' => $tenantForSale->id,
            'sale_currency_code' => $saleCurrencyCode,
            'sale_rate_to_bs' => $saleRateToBs > 0 ? round($saleRateToBs, 6) : null,
        ]);
    
        $itemsSubtotal = 0.0;

        // Crear detalles de la venta y actualizar stock
        foreach ($groupedData as $detail) {
            if (in_array($deliveryType, ['delivery', 'shipping'], true) && (int) ($detail['quantity'] ?? 0) > 1) {
                return response()->json(['error' => 'Las ventas con delivery/envío solo permiten cantidades al detal (1 unidad por ítem).'], 422);
            }

            SalesOrderDetail::create([
                'sales_order_id' => $salesOrder->id,
                'product_variant_id' => $detail['product_variant_id'],
                'quantity' => $detail['quantity'],
                'price' => $detail['price'],
                'amount' => $detail['amount'],
            ]);
            $itemsSubtotal += (float) $detail['amount'];
    
            // Actualizar el stock
            $productVariant = ProductVariant::find($detail['product_variant_id']);
            if ($productVariant && $productVariant->stock >= $detail['quantity']) {
                $productVariant->stock -= $detail['quantity'];
                $productVariant->save();
            } else {
                return response()->json(['error' => 'Stock insuficiente para el producto: ' . ((int) ($detail['product_variant_id'] ?? 0))], 400);
            }
        }

        $salesOrder->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);
        $this->syncSellerCommissionForOrder($salesOrder);

        try {
            $deliveryPricing = DeliveryManager::calculate($tenantForSale, $deliveryType, $itemsSubtotal, $deliveryDistanceKm);
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }
        $salesOrder->delivery_fee = $deliveryPricing['fee'];
        $salesOrder->delivery_fee_mode = $deliveryPricing['mode'];
        $salesOrder->delivery_distance_km = $deliveryPricing['distance_km'];
        $salesOrder->save();
    
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

        WorkflowNotifier::notifyTenantRoles((int) $tenantForSale->id, ['owner', 'administrador', 'admin', 'vendedor'], [
            'title' => 'Nueva compra de cliente',
            'message' => 'Se creó el pedido #' . $salesOrder->id . '. Revisa venta y métodos de pago.',
            'type' => 'new-order',
            'tenant_id' => $tenantForSale->id,
            'order_id' => $salesOrder->id,
            'action' => 'review_order_and_payments',
        ]);
    
        return response()->json(['message' => 'Venta en eCommerce registrada exitosamente.'], 200);
    }
    

    public function viewOrders()
    {
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);
        $isSeller = (bool) ($user?->hasStoreRole('seller') ?? false);

        $salesOrdersQuery = SalesOrder::with([
            'user', 
            'salesRepresentative',
            'details', 
            'details.variant', 
            'payments',
            'electronicDocuments',
            'returns.items',
        ])->where('tenant_id', $user->tenant_id);

        if ($isSeller) {
            $salesOrdersQuery->where('sales_rep_user_id', (int) $user->id);
        }

        $salesOrders = $salesOrdersQuery
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $salesOrders->setCollection($salesOrders->getCollection()->map(function (SalesOrder $order) {
            $order->total_items = $order->details->sum('quantity');
            $order->has_returns = $order->returns->isNotEmpty();
            $order->latest_electronic_document = $this->resolvePrimaryInvoiceDocument($order);
            $order->has_annulled_invoice = (bool) optional($order->latest_electronic_document)->is_annulled;
            return $order;
        }));

        $isWarehouse = $user?->hasStoreRole('warehouse') ?? false;
        $isDelivery = $user?->hasStoreRole('delivery') ?? false;
        $canApprovePayments = !($isWarehouse || $isDelivery);
        $canDeliverOrders = $isSeller || $isWarehouse || $isDelivery || ($user?->isAdmin() ?? false) || ($user?->isOwner() ?? false);
        $pageTitle = 'VENTAS REALIZADAS';
        $isPendingDeliveryView = false;
        $pendingDispatchGuideAlert = ($tenant?->electronic_invoicing_enabled ?? false)
            ? $this->buildPendingDispatchGuideAlertData($salesOrders->getCollection(), $tenant)
            : null;
    
        return view('salesOrders', compact('salesOrders', 'canApprovePayments', 'canDeliverOrders', 'pageTitle', 'isPendingDeliveryView', 'pendingDispatchGuideAlert'));
    }

    public function sendPendingDispatchGuidesReport(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::find((int) $user->tenant_id);

        $validated = $request->validate([
            'period' => 'required|in:fortnight,monthly',
            'email' => 'required|string|max:500',
        ]);

        $emails = collect(explode(',', (string) $validated['email']))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return back()->with('error', 'Debes indicar al menos un correo de destino para el reporte al SENIAT.');
        }

        $report = $this->buildPendingDispatchGuidesPeriodReport((int) $user->tenant_id, (string) $validated['period']);

        try {
            Mail::to($emails->all())->send(new PendingDispatchGuidesReportMail($tenant, $report));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar el reporte de guías pendientes por facturar', [
                'tenant_id' => $user->tenant_id,
                'period' => $validated['period'],
                'emails' => $emails->all(),
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'No se pudo enviar el reporte al correo indicado. ' . $exception->getMessage());
        }

        return back()->with('success', 'Reporte de guías pendientes enviado correctamente (' . ($validated['period'] === 'fortnight' ? 'quincenal' : 'mensual') . ').');
    }

    public function viewPendingDeliveryOrders()
    {
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);
        $planCapabilities = TenantPlanCapabilities::forTenant($tenant);

        if (!$planCapabilities->canPendingOrders() || !$planCapabilities->allowsDeliveryOperations()) {
            return redirect()->route('sales.orders')
                ->with('warning', 'El plan actual no permite gestionar pedidos pendientes de delivery.');
        }

        $salesOrders = SalesOrder::with(['user', 'salesRepresentative', 'details', 'details.variant', 'payments', 'electronicDocuments', 'returns.items'])
            ->where('tenant_id', $user->tenant_id)
            ->where('deliver_status', 0)
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhereHas('payments', function ($paymentQuery) {
                        $paymentQuery->where('status', 1);
                    });
            })
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $salesOrders->setCollection($salesOrders->getCollection()->map(function (SalesOrder $order) {
            $order->total_items = $order->details->sum('quantity');
            $order->has_returns = $order->returns->isNotEmpty();
            $order->latest_electronic_document = $this->resolvePrimaryInvoiceDocument($order);
            $order->has_annulled_invoice = (bool) optional($order->latest_electronic_document)->is_annulled;
            return $order;
        }));

        $isSeller = $user?->hasStoreRole('seller') ?? false;
        $isWarehouse = $user?->hasStoreRole('warehouse') ?? false;
        $isDelivery = $user?->hasStoreRole('delivery') ?? false;
        $canApprovePayments = !($isWarehouse || $isDelivery);
        $canDeliverOrders = $isSeller || $isWarehouse || $isDelivery || ($user?->isAdmin() ?? false) || ($user?->isOwner() ?? false);
        $pageTitle = 'PEDIDOS PENDIENTES DE ENTREGA';
        $isPendingDeliveryView = true;

        return view('salesOrders', compact('salesOrders', 'canApprovePayments', 'canDeliverOrders', 'pageTitle', 'isPendingDeliveryView'));
    }

    public function viewReceivables()
    {
        $user = auth()->user();

        $salesOrders = SalesOrder::with(['user', 'details', 'payments', 'retentions'])
            ->where('tenant_id', $user->tenant_id)
            ->where('status', '!=', 2)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->total_items = (int) $order->details->sum('quantity');
                $order->order_total_amount = (float) $order->gross_total;
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->retentions_amount = (float) $order->retentions->sum('retained_amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount - $order->retentions_amount, 2));

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
        $tenant = Tenant::find($user->tenant_id);
        $planCapabilities = TenantPlanCapabilities::forTenant($tenant);

        if (!$planCapabilities->canPaidPendingDeliveries() || !$planCapabilities->allowsDeliveryOperations()) {
            return redirect()->route('sales.orders')
                ->with('warning', 'El plan actual no permite usar la bandeja de entregas pendientes.');
        }

        $salesOrders = SalesOrder::with([
                'user:id,name',
                'details:id,sales_order_id,quantity,amount,product_variant_id',
                'payments:id,sales_order_id,status,amount',
                'assignedDeliveryUser:id,name',
                'retentions:id,sales_order_id,retained_amount',
            ])
            ->where('tenant_id', $user->tenant_id)
            ->where('deliver_status', 0)
            ->where('status', '!=', 2)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->total_items = (int) $order->details->sum('quantity');
                $order->order_total_amount = (float) $order->gross_total;
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->registered_paid_amount = (float) $order->payments->where('status', '!=', 3)->sum('amount');
                $order->effective_paid_amount = max($order->approved_paid_amount, $order->registered_paid_amount);
                $order->retentions_amount = (float) $order->retentions->sum('retained_amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->effective_paid_amount - $order->retentions_amount, 2));

                $deliveryMeta = $this->extractDeliveryOrderMeta($order);
                $order->delivery_receiver_name = $deliveryMeta['receiver_name'];
                $order->delivery_receiver_phone = $deliveryMeta['receiver_phone'];
                $order->delivery_destination_label = $deliveryMeta['destination_label'];
                $order->delivery_extra_info = $deliveryMeta['extra_info'];
                $order->delivery_map_url = $deliveryMeta['map_url'];
                $order->assigned_delivery_name = trim((string) ($order->assignedDeliveryUser->name ?? ''));

                return $order;
            })
            ->filter(fn (SalesOrder $order) => $order->pending_amount <= 0.0001)
            ->values();

        $pickupOrders = $salesOrders->filter(function (SalesOrder $order) {
            $preference = mb_strtolower(trim((string) ($order->preference ?? '')));

            if (str_contains($preference, 'delivery')) {
                return false;
            }

            if (str_contains($preference, 'env') || str_contains($preference, 'shipping')) {
                return false;
            }

            return true;
        })->values();

        $deliveryOrders = $salesOrders->filter(function (SalesOrder $order) {
            $preference = mb_strtolower(trim((string) ($order->preference ?? '')));

            return str_contains($preference, 'delivery');
        })->values();

        $shippingOrders = $salesOrders->filter(function (SalesOrder $order) {
            $preference = mb_strtolower(trim((string) ($order->preference ?? '')));

            return str_contains($preference, 'env') || str_contains($preference, 'shipping');
        })->values();

        $isOwner = (bool) ($user?->isOwner() ?? false);
        $isAdmin = (bool) ($user?->isAdmin() ?? false);
        $isSeller = (bool) ($user?->hasStoreRole('seller') ?? false);
        $isWarehouse = (bool) ($user?->hasStoreRole('warehouse') ?? false);
        $isDelivery = (bool) ($user?->hasStoreRole('delivery') ?? false);
        $isManager = $isOwner || $isAdmin;

        if ($isDelivery) {
            $deliveryOrders = $deliveryOrders->filter(function (SalesOrder $order) use ($user) {
                $assignedUserId = (int) ($order->delivery_assigned_user_id ?? 0);

                return $assignedUserId === 0 || $assignedUserId === (int) ($user->id ?? 0);
            })->values();
        }

        $canManagePickupOrders = $isManager || $isSeller || $isWarehouse;
        $canManageDeliveryOrders = $isManager || $isSeller || $isWarehouse || $isDelivery;
        $canManageShippingOrders = $isManager || $isWarehouse;

        $deliveryUsers = User::query()
            ->with('role')
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', 1)
            ->get()
            ->filter(fn (User $candidate) => $candidate->hasStoreRole('delivery'))
            ->values();

        $visibleTabs = collect([
            [
                'key' => 'pickup',
                'label' => 'Retiro en tienda',
                'orders' => $pickupOrders,
                'canManage' => $canManagePickupOrders,
            ],
            [
                'key' => 'delivery',
                'label' => 'Delivery',
                'orders' => $deliveryOrders,
                'canManage' => $canManageDeliveryOrders,
            ],
            [
                'key' => 'shipping',
                'label' => 'Envío',
                'orders' => $shippingOrders,
                'canManage' => $canManageShippingOrders,
            ],
        ])->filter(fn (array $tab) => (bool) $tab['canManage'])->values();

        $activeTab = (string) optional($visibleTabs->first())['key'];
        $visibleOrders = collect([$pickupOrders, $deliveryOrders, $shippingOrders])->flatten(1);
        $ordersCount = (int) $visibleOrders->count();
        $totalPaidOrdersAmount = (float) $visibleOrders->sum('effective_paid_amount');

        return view('paidPendingDeliveries', compact(
            'salesOrders',
            'ordersCount',
            'totalPaidOrdersAmount',
            'pickupOrders',
            'deliveryOrders',
            'shippingOrders',
            'visibleTabs',
            'activeTab',
            'deliveryUsers'
        ));
    }

    public function viewOrdersReport(Request $request)
    {
        $user = auth()->user();
        $isSeller = (bool) ($user?->hasStoreRole('seller') ?? false);

        if ($isSeller) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para generar reportes.',
            ], 403);
        }

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
        ->where('tenant_id', (int) ($user->tenant_id ?? 0))
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
                $total = (float) $order->gross_total;

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
        $authUser = auth()->user();
        $order = SalesOrder::with([
            'user',
            'salesRepresentative',
            'tenant',
            'assignedDeliveryUser',
            'details',
            'details.variant',
            'details.variant.product',
            'details.taxes',
            'payments',
            'payments.payment',
            'payments.images',
            'electronicDocuments',
            'adjustmentNotes',
            'retentions',
            'returns.items',
        ])->find($id);

        if (!$order) {
            abort(404);
        }

        abort_if((int) ($order->tenant_id ?? 0) !== (int) ($authUser->tenant_id ?? 0), 404);

        $isSeller = (bool) ($authUser?->hasStoreRole('seller') ?? false);
        if ($isSeller && (int) ($order->sales_rep_user_id ?? 0) !== (int) ($authUser->id ?? 0)) {
            abort(404);
        }

        // Calcular el total de la orden
        $totalOrden = (float) $order->gross_total;
        // Calcular el total pagado
        $totalPagado = $order->payments->sum(function ($payment) {
            return $payment->amount;
        });
        $totalDevuelto = $order->returns->flatMap->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        $totalOrden = $order->totalAfterReturns((float) $totalDevuelto);
        $totalPagado = $order->payments->sum('amount');
        $saldo = $totalOrden - $totalPagado; // si es negativo, se debe dar vuelto
        $order->saldo = $saldo; // Agregar saldo al objeto de la orden
        $order->total_devuelto = $totalDevuelto; // Agregar total devuelto al objeto de la orden
        $order->total_pagado = $totalPagado; // Agregar total pagado al objeto de la orden
        $order->total_orden = $totalOrden; // Agregar total de la orden al objeto de la orden
        $order->has_returns = $order->returns->isNotEmpty(); // Verificar si tiene devoluciones
        $order->latest_electronic_document = $this->resolvePrimaryInvoiceDocument($order);
        $order->latest_dispatch_guide_document = $this->resolveLatestDispatchGuideDocument($order);
        $order->has_annulled_invoice = (bool) optional($order->latest_electronic_document)->is_annulled;
        $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
        $orderCurrencySymbol = TenantCurrency::resolveCurrencySymbol($orderCurrencyCode);
        $deliveryMeta = $this->extractDeliveryOrderMeta($order);
        $deliveryUsers = User::query()
            ->with('role')
            ->where('tenant_id', $order->tenant_id)
            ->get()
            ->filter(fn (User $candidate) => $candidate->hasStoreRole('delivery'))
            ->values();

        $linkedAppointment = Appointment::query()
            ->with(['service', 'assignedUser', 'customer', 'paymentMethod.currency'])
            ->where('tenant_id', (int) $order->tenant_id)
            ->where('sales_order_id', (int) $order->id)
            ->latest('id')
            ->first();

        $appointmentPaymentMethods = collect();
        $paymentMethods = PaymentMethod::query()
            ->with('currency')
            ->where('tenant_id', (int) $order->tenant_id)
            ->active()
            ->orderBy('name')
            ->get();

        if ($linkedAppointment) {
            $appointmentPaymentMethods = PaymentMethod::query()
                ->with('currency')
                ->where('tenant_id', (int) $order->tenant_id)
                ->active()
                ->orderBy('name')
                ->get();
        }

        return view('salesOrderDetail', compact(
            'order',
            'totalOrden',
            'totalPagado',
            'orderCurrencyCode',
            'orderCurrencySymbol',
            'deliveryMeta',
            'deliveryUsers',
            'linkedAppointment',
            'appointmentPaymentMethods',
            'paymentMethods'
        ));
    }

    public function createPaymentEntry($orderId, Request $request)
    {
        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['success' => false, 'message' => 'No autorizado para registrar pagos.'], 403);
        }

        $order = SalesOrder::query()->with(['tenant'])->findOrFail($orderId);
        if ((int) ($order->tenant_id ?? 0) !== (int) ($user->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado para esta orden.'], 403);
        }

        if ((bool) ($user?->hasStoreRole('seller') ?? false)
            && (int) ($order->sales_rep_user_id ?? 0) !== (int) ($user->id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado para esta orden.'], 403);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'reference' => 'nullable|string|max:255',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $paymentMethod = PaymentMethod::query()
            ->with('currency')
            ->where('tenant_id', (int) $order->tenant_id)
            ->active()
            ->find($validated['payment_method_id']);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'El método de pago seleccionado está inactivo o no pertenece a esta tienda.',
            ], 422);
        }

        $reference = trim((string) ($validated['reference'] ?? ''));
        if ($paymentMethod->usesReference() && $reference === '') {
            return response()->json([
                'success' => false,
                'message' => 'Este método de pago requiere referencia.',
            ], 422);
        }

        if ($paymentMethod->requiresProofImage() && !$request->hasFile('proof_image')) {
            return response()->json([
                'success' => false,
                'message' => 'Este método de pago requiere comprobante.',
            ], 422);
        }

        $amountOriginal = round((float) ($validated['amount'] ?? 0), 2);
        $currencyCode = strtoupper(trim((string) ($paymentMethod->currency->code ?? 'USD')));
        $requestedCurrencyCode = strtoupper(trim((string) ($validated['currency'] ?? $currencyCode)));
        if ($requestedCurrencyCode !== '' && $requestedCurrencyCode !== $currencyCode) {
            return response()->json([
                'success' => false,
                'message' => 'La moneda seleccionada no coincide con el método de pago.',
            ], 422);
        }

        $tenantBaseCurrency = TenantCurrency::resolveBaseCurrencyCode($order->tenant);
        $rateToBs = (float) ($order->sale_rate_to_bs ?? $order->change_rate_to_bs ?? 0);

        if ($tenantBaseCurrency === 'BS') {
            $rateToBs = 1.0;
        }

        if ($rateToBs <= 0) {
            $rateToBs = (float) TenantCurrency::resolveRateToBs((int) $order->tenant_id, $tenantBaseCurrency);
            if ($tenantBaseCurrency === 'BS') {
                $rateToBs = 1.0;
            }
        }

        $amountBase = $amountOriginal;
        $exchangeRateToBase = 1.0;

        if ($currencyCode === 'BS' && $tenantBaseCurrency !== 'BS') {
            if ($rateToBs <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay tasa de cambio configurada para convertir pagos en Bs.',
                ], 422);
            }

            $amountBase = round($amountOriginal / $rateToBs, 2);
            $exchangeRateToBase = $rateToBs;
        } elseif ($currencyCode !== 'BS' && $tenantBaseCurrency === 'BS') {
            if ($rateToBs <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay tasa de cambio configurada para convertir pagos en moneda extranjera.',
                ], 422);
            }

            $amountBase = round($amountOriginal * $rateToBs, 2);
            $exchangeRateToBase = $rateToBs;
        }

        $totalOrden = (float) ($order->gross_total ?? 0);
        $totalPagadoActual = (float) $order->payments()->sum('amount');
        $pendingBeforeCreate = max(0, $totalOrden - $totalPagadoActual);

        if ($amountBase - $pendingBeforeCreate > 0.00001) {
            return response()->json([
                'success' => false,
                'message' => 'El monto ingresado supera el saldo pendiente de la orden.',
            ], 422);
        }

        $payment = Payment::create([
            'sales_order_id' => (int) $order->id,
            'payment_method' => (int) $paymentMethod->id,
            'amount' => $amountBase,
            'amount_original' => $amountOriginal,
            'amount_base' => $amountBase,
            'exchange_rate_to_base' => $exchangeRateToBase > 0 ? $exchangeRateToBase : null,
            'applies_igtf' => false,
            'currency' => $currencyCode,
            'reference' => $reference !== '' ? $reference : null,
            'status' => 0,
        ]);

        if ($request->hasFile('proof_image')) {
            $proofPath = ImageStorage::storeUploadedImageAsWebp(
                $request->file('proof_image'),
                'payment_images'
            );

            PaymentImage::create([
                'payment_id' => $payment->id,
                'image_path' => $proofPath,
            ]);
        }

        $order->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);
        $this->syncSellerCommissionForOrder($order);

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente. Queda en estado En Proceso hasta su aprobación.',
        ]);
    }

    public function assignDeliveryUser(SalesOrder $order, Request $request)
    {
        $user = auth()->user();
        $canManageAnyDeliveryAssignment = $user?->hasStoreRole('owner', 'admin', 'seller', 'warehouse') ?? false;
        $canSelfAssignDelivery = $user?->hasStoreRole('delivery') ?? false;

        $tenant = Tenant::find($order->tenant_id);
        if (!TenantPlanCapabilities::forTenant($tenant)->allowsDeliveryOperations()) {
            return back()->with('error', 'El plan Free no permite asignar ni gestionar repartidores.');
        }

        if (!$canManageAnyDeliveryAssignment && !$canSelfAssignDelivery) {
            return back()->with('error', 'No autorizado para asignar repartidores.');
        }

        if ((int) $order->tenant_id !== (int) ($user->tenant_id ?? 0)) {
            abort(404);
        }

        if ($canSelfAssignDelivery && !$canManageAnyDeliveryAssignment) {
            $assignedUserId = (int) ($request->input('delivery_assigned_user_id') ?: ($user->id ?? 0));

            if ($assignedUserId !== (int) ($user->id ?? 0)) {
                return back()->with('error', 'Solo puedes autoasignarte órdenes para entrega.');
            }
        } else {
            $validated = $request->validate([
                'delivery_assigned_user_id' => 'nullable|integer|exists:users,id',
            ]);

            $assignedUserId = (int) ($validated['delivery_assigned_user_id'] ?? 0);
        }

        $result = DB::transaction(function () use ($order, $user, $assignedUserId, $canManageAnyDeliveryAssignment, $canSelfAssignDelivery) {
            /** @var SalesOrder $lockedOrder */
            $lockedOrder = SalesOrder::query()
                ->with(['user', 'assignedDeliveryUser'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousAssignedUserId = (int) ($lockedOrder->delivery_assigned_user_id ?? 0);

            if ($canSelfAssignDelivery && !$canManageAnyDeliveryAssignment && $previousAssignedUserId > 0 && $previousAssignedUserId !== (int) ($user->id ?? 0)) {
                return [
                    'ok' => false,
                    'message' => 'Esta orden ya fue tomada por otro delivery.',
                    'status' => 409,
                ];
            }

            $deliveryUser = null;

            if ($assignedUserId > 0) {
                $deliveryUser = User::query()->with('role')->findOrFail($assignedUserId);

                if ((int) $deliveryUser->tenant_id !== (int) $lockedOrder->tenant_id || !$deliveryUser->hasStoreRole('delivery')) {
                    return [
                        'ok' => false,
                        'message' => 'Debes seleccionar un usuario con rol delivery de esta tienda.',
                        'status' => 422,
                    ];
                }
            }

            $lockedOrder->delivery_assigned_user_id = $assignedUserId > 0 ? $assignedUserId : null;
            $hasChanged = $previousAssignedUserId !== (int) ($lockedOrder->delivery_assigned_user_id ?? 0);

            if ($hasChanged) {
                $lockedOrder->save();
            }

            $lockedOrder->load('assignedDeliveryUser', 'user');

            if ($hasChanged) {
                try {
                    event(new DeliveryAssignmentUpdated($lockedOrder, $user));
                } catch (\Throwable $exception) {
                    Log::warning('No se pudo emitir el evento realtime de asignacion delivery.', [
                        'order_id' => $lockedOrder->id,
                        'tenant_id' => $lockedOrder->tenant_id,
                        'delivery_assigned_user_id' => $lockedOrder->delivery_assigned_user_id,
                        'actor_user_id' => $user->id ?? null,
                        'error' => $exception->getMessage(),
                    ]);
                }

                if ($assignedUserId > 0) {
                    WorkflowNotifier::notifyUser($lockedOrder->user, [
                        'title' => 'Delivery asignado a tu orden',
                        'message' => 'Tu orden #' . $lockedOrder->id . ' ya fue asignada a ' . trim((string) ($lockedOrder->assignedDeliveryUser->name ?? 'un repartidor')) . '.',
                        'type' => 'delivery-assigned',
                        'tenant_id' => $lockedOrder->tenant_id,
                        'order_id' => $lockedOrder->id,
                        'action' => 'delivery_assigned',
                        'meta' => [
                            'delivery_user_id' => (int) ($lockedOrder->delivery_assigned_user_id ?? 0),
                            'delivery_user_name' => trim((string) ($lockedOrder->assignedDeliveryUser->name ?? '')),
                            'assigned_by_user_id' => (int) ($user->id ?? 0),
                            'assigned_by_user_name' => trim((string) ($user->name ?? '')),
                        ],
                    ]);
                }
            }

            if (!$hasChanged && $assignedUserId > 0 && $assignedUserId === (int) ($user->id ?? 0) && $canSelfAssignDelivery && !$canManageAnyDeliveryAssignment) {
                $message = 'La orden ya estaba asignada para ti.';
            } elseif (!$hasChanged && $assignedUserId > 0) {
                $message = 'La orden ya estaba asignada a ese repartidor.';
            } elseif (!$hasChanged) {
                $message = 'La orden ya no tenía repartidor asignado.';
            } elseif ($assignedUserId > 0 && $canSelfAssignDelivery && !$canManageAnyDeliveryAssignment) {
                $message = 'Te asignaste esta orden correctamente.';
            } elseif ($assignedUserId > 0) {
                $message = 'Repartidor asignado correctamente.';
            } else {
                $message = 'Asignación de repartidor eliminada.';
            }

            return [
                'ok' => true,
                'message' => $message,
                'status' => 200,
            ];
        });

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
    public function showPublicOrder($id)
    {
        $order = SalesOrder::with([
            'user',
            'tenant',
            'assignedDeliveryUser',
            'details',
            'details.variant',
            'details.variant.product',
            'payments',
            'payments.payment',
            'payments.images',
            'electronicDocuments',
            'returns.items',
        ])->find($id);
        // Calcular el total de la orden
        $totalOrden = (float) $order->gross_total;
        // Calcular el total pagado
        $totalPagado = $order->payments->sum(function ($payment) {
            return $payment->amount;
        });

        $totalDevuelto = $order->returns->flatMap->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $order->total_devuelto = $totalDevuelto;
        $order->has_returns = $order->returns->isNotEmpty();
        $order->latest_electronic_document = $this->resolvePrimaryInvoiceDocument($order);
        $order->has_annulled_invoice = (bool) optional($order->latest_electronic_document)->is_annulled;

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
        try {
            $order = SalesOrder::with(['user', 'details', 'details.variant.product', 'details.taxes', 'payments.payment'])->findOrFail($id);
            $authUser = auth()->user();
            $isWarehouseOnly = ($authUser?->hasStoreRole('warehouse') ?? false)
                && !($authUser?->hasStoreRole('owner', 'admin', 'seller') ?? false);

            if ($request->routeIs('public.order.pdf')
                && $type === 'invoice'
                && !(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)
            ) {
                abort(404);
            }

            if ($isWarehouseOnly && $type === 'invoice') {
                abort(403, 'El rol Almacenista solo puede descargar la orden de entrega.');
            }

            if ($type === 'invoice' && ($order->document_issue_mode ?? 'delivery_note') === 'electronic_invoice') {
                return $this->downloadElectronicInvoicePdf(
                    $request,
                    $order,
                    (string) $request->query('disposition', 'attachment')
                );
            }

            $orderCurrencyCode = $this->resolveOrderCurrencyCode($order);
            $emissionCurrencyCode = $this->resolveEmissionCurrencyCode((string) $request->query('currency_code', ''), $orderCurrencyCode);

            // If requested currency matches order currency, prefer cached/generated stored PDF.
            $shouldRenderByCurrency = $request->has('currency_code') && $emissionCurrencyCode !== $orderCurrencyCode;

            if ($shouldRenderByCurrency) {
                try {
                    return $this->downloadRenderedPdfByCurrency($request, $order, $type, $emissionCurrencyCode);
                } catch (\Throwable $exception) {
                    Log::warning('Fallo al renderizar PDF por moneda; se usa PDF almacenado.', [
                        'order_id' => (int) $order->id,
                        'tenant_id' => (int) ($order->tenant_id ?? 0),
                        'pdf_type' => (string) $type,
                        'requested_currency_code' => (string) $request->query('currency_code', ''),
                        'resolved_currency_code' => (string) $emissionCurrencyCode,
                        'order_currency_code' => (string) $orderCurrencyCode,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $assets = $this->ensureAssociatedPdfAssets($order, false);
            $filePath = $type === 'delivery' ? $assets['delivery_path'] : $assets['invoice_path'];

            if (!is_file($filePath)) {
                throw new \RuntimeException('No se encontro el archivo PDF generado para la orden.');
            }

            $fileName = basename($filePath);

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) $request->query('disposition', 'attachment')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('No se pudo entregar el PDF de la orden.', [
                'order_id' => (int) $id,
                'pdf_type' => (string) $type,
                'requested_currency_code' => (string) $request->query('currency_code', ''),
                'error' => $exception->getMessage(),
            ]);

            abort(422, 'No se pudo generar el PDF de la orden en este momento. Intenta nuevamente.');
        }
    }

    private function ensureAssociatedPdfAssets(SalesOrder $order, bool $generateMissing = true): array
    {
        $invoiceRelative = 'orders/factura-' . $order->id . '.pdf';
        $deliveryRelative = 'orders/' . $this->resolveInternalDispatchFilename((int) $order->id);
        $legacyDeliveryRelative = 'orders/NotaEntrega-' . $order->id . '.pdf';

        if (!Storage::disk('public')->exists($deliveryRelative) && Storage::disk('public')->exists($legacyDeliveryRelative)) {
            $deliveryRelative = $legacyDeliveryRelative;
        }

        if ($generateMissing && (!Storage::disk('public')->exists($invoiceRelative) || !Storage::disk('public')->exists($deliveryRelative))) {
            $this->generateAssociatedPdfAssets($order);
            if (!Storage::disk('public')->exists($deliveryRelative) && Storage::disk('public')->exists($legacyDeliveryRelative)) {
                $deliveryRelative = $legacyDeliveryRelative;
            }
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

        $imageBase64 = $this->resolveTenantBillingLogoDataUri($order->tenant ?? null);

        $totalOrden = (float) $order->gross_total;
        $totalTaxes = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalPagado = (float) $order->payments->sum('amount');
        $totalGeneral = $totalOrden + $totalTaxes;
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $order->tenant_id)->first();
        $tienda = $order->tenant;

        $qrUrl = url('/publicOrder/' . $order->id);
        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);

        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage->getString());

        $deliveryPdfView = $this->resolveDeliveryPdfViewName();

        $deliveryHtml = view($deliveryPdfView, compact(
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

        $deliveryOutput = $this->renderPdfOutput($deliveryHtml);

        $invoiceRelative = 'orders/factura-' . $order->id . '.pdf';
        $deliveryRelative = 'orders/' . $this->resolveInternalDispatchFilename((int) $order->id);

        if (($order->document_issue_mode ?? 'delivery_note') !== 'electronic_invoice') {
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

            $invoiceOutput = $this->renderPdfOutput($invoiceHtml);
            Storage::disk('public')->put($invoiceRelative, $invoiceOutput);
        }

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

    private function downloadRenderedPdfByCurrency(Request $request, SalesOrder $order, string $type, string $emissionCurrencyCode)
    {
        $order->loadMissing(['details.taxes', 'details.variant.product', 'payments.payment', 'tenant']);

        if ($type === 'invoice' && ($order->document_issue_mode ?? 'delivery_note') === 'electronic_invoice') {
            return $this->downloadElectronicInvoicePdf($request, $order, (string) $request->query('disposition', 'attachment'));
        }

        $imageBase64 = $this->resolveTenantBillingLogoDataUri($order->tenant ?? null);

        $totalOrden = (float) $order->gross_total;
        $totalTaxes = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalPagado = (float) $order->payments->sum('amount');
        $totalGeneral = $totalOrden + $totalTaxes;
        $dollarRate = DollarRate::latest('created_at')->where('tenant_id', $order->tenant_id)->first();
        $tienda = $order->tenant;
        $pdfCurrencyContext = $this->buildPdfCurrencyContext($order, $emissionCurrencyCode);

        $qrUrl = url('/publicOrder/' . $order->id);
        $qrCode = QrCode::create($qrUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(250)
            ->setMargin(10);

        $writer = new PngWriter();
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($writer->write($qrCode)->getString());

        $viewName = $type === 'delivery'
            ? $this->resolveDeliveryPdfViewName()
            : 'fiscalOrderPdf';
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
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) $request->query('disposition', 'attachment')),
        ]);
    }

    private function resolveDeliveryPdfViewName(): string
    {
        $authUser = auth()->user();
        $isWarehouseOnly = ($authUser?->hasStoreRole('warehouse') ?? false)
            && !($authUser?->hasStoreRole('owner', 'admin', 'seller') ?? false);

        if ($isWarehouseOnly) {
            return 'orderPdfWarehouse';
        }

        return 'orderPdf';
    }

    private function downloadElectronicInvoicePdf(Request $request, SalesOrder $order, string $disposition = 'attachment')
    {
        $order->loadMissing('electronicDocuments');

        $document = $order->electronicDocuments->sortByDesc('id')->first();
        if (!$document) {
            abort(404, 'No existe factura fiscal emitida por la imprenta autorizada para esta venta.');
        }

        $service = app(TheFactoryHkaService::class);
        if (!$service->isConfigured()) {
            abort(422, 'La integración de facturación digital no está configurada en el servidor.');
        }

        $response = $service->downloadDocumentFile([
            'serie' => $document->serie,
            'tipoDocumento' => $document->tipo_documento ?: '01',
            'numeroDocumento' => $document->numero_documento,
            'tipoArchivo' => 'pdf',
        ]);

        if (!($response['ok'] ?? false) || empty($response['content'])) {
            abort(422, 'No fue posible descargar la factura fiscal desde la imprenta autorizada. ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $document->update([
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'response_payload' => Arr::except((array) ($response['data'] ?? []), ['archivo']) ?: $document->response_payload,
        ]);

        $fileName = implode('-', array_filter([
            'factura-fiscal',
            trim((string) ($document->serie ?? '')) !== '' ? trim((string) $document->serie) : null,
            trim((string) ($document->numero_documento ?? '')),
        ])) . '.pdf';

        return response((string) $response['content'], 200, [
            'Content-Type' => (string) ($response['mime_type'] ?? 'application/pdf'),
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, $disposition),
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
            if ((bool) ($variant->product->is_consumable ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código corresponde a un producto consumible y no puede venderse de forma directa.',
                ], 422);
            }

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

        $reason = null;
        if ((int) $request->status === 2) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para negar o cancelar la orden.');
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
        $this->syncSellerCommissionForOrder($order);

        if ((int) $order->status === 2) {
            ActionReason::log('sales_orders', 'ORDER_CANCELLED', (string) $reason, [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
            ]);
        }

        WorkflowNotifier::notifyUser($order->user, [
            'title' => 'Estado de pedido actualizado',
            'message' => 'Tu pedido #' . $order->id . ' cambió de estado.',
            'type' => 'order-status',
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'action' => 'order_status_updated',
        ]);

        if ((int) $order->status === 1) {
            $order->loadMissing('tenant');
            if ($order->tenant) {
                $this->notifyDeliveryTeamIfEnabled($order->tenant, $order, 'El pedido #' . $order->id . ' fue aprobado y está listo para gestionar entrega.');
            }

            $this->attemptElectronicEmission($order);
        }
    
        // Si el nuevo estado es 1, generar el PDF y enviar el correo
        if ($order->status == 1) {
            // Cargar el logo de facturación y convertirlo a base64
            $imageBase64 = $this->resolveTenantBillingLogoDataUri($order->tenant ?? null);
    
            // Calcular totales
            $totalOrden = (float) $order->gross_total;
            $totalPagado = $order->payments->sum('amount');
    
            // Generar el código QR correctamente con Endroid QR Code
            $qrUrl = url('/publicOrder/' . $order->id);
            
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

    public function downloadHkaDispatchGuide(Request $request, SalesOrder $order)
    {
        $authUser = auth()->user();
        if ((int) ($order->tenant_id ?? 0) !== (int) ($authUser->tenant_id ?? 0)) {
            abort(404);
        }

        if (!$authUser?->hasStoreRole('owner', 'admin', 'seller', 'warehouse')) {
            abort(403, 'No autorizado para descargar la guía de despacho fiscal.');
        }

        $validated = $request->validate([
            'tipo_archivo' => 'nullable|in:pdf,PDF,xml,XML,json,JSON',
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $service = app(TheFactoryHkaService::class);
        if (!$service->isConfigured()) {
            return back()->with('error', 'La integración de facturación digital no está configurada en el servidor.');
        }

        $order->loadMissing('electronicDocuments');
        $guideDocument = $this->resolveLatestDispatchGuideDocument($order);
        if (!$guideDocument) {
            return back()->with('error', 'No existe una guía de despacho fiscal emitida para esta venta.');
        }

        if (!$guideDocument->issued_at) {
            $guideReason = trim((string) ($guideDocument->mensaje ?? ''));
            return back()->with('error', 'La guía de despacho fiscal aún no está emitida en HKA.' . ($guideReason !== '' ? ' Detalle: ' . $guideReason : ''));
        }

        if ((bool) $guideDocument->is_annulled) {
            return back()->with('error', 'La guía de despacho fiscal fue anulada y ya no puede descargarse.');
        }

        $response = $service->downloadDocumentFile([
            'serie' => $guideDocument->serie,
            'tipoDocumento' => $guideDocument->tipo_documento ?: '04',
            'numeroDocumento' => $guideDocument->numero_documento,
            'tipoArchivo' => $validated['tipo_archivo'] ?? 'pdf',
        ]);

        if (!($response['ok'] ?? false) || empty($response['content'])) {
            return back()->with('error', 'No fue posible descargar la guía de despacho fiscal desde HKA: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $guideDocument->update([
            'mensaje' => (string) ($response['message'] ?? $guideDocument->mensaje),
            'response_payload' => Arr::except((array) ($response['data'] ?? []), ['archivo']) ?: $guideDocument->response_payload,
        ]);

        $extension = (string) ($response['extension'] ?? strtolower((string) ($validated['tipo_archivo'] ?? 'pdf')));
        $fileName = implode('-', array_filter([
            'guia-despacho-fiscal',
            trim((string) ($guideDocument->serie ?? '')) !== '' ? trim((string) $guideDocument->serie) : null,
            trim((string) ($guideDocument->numero_documento ?? '')),
        ])) . '.' . strtolower($extension);

        return response((string) $response['content'], 200, [
            'Content-Type' => (string) ($response['mime_type'] ?? 'application/pdf'),
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) ($validated['disposition'] ?? 'attachment')),
        ]);
    }

    public function emitHkaDispatchGuide(SalesOrder $order)
    {
        $authUser = auth()->user();
        if ((int) ($order->tenant_id ?? 0) !== (int) ($authUser->tenant_id ?? 0)) {
            abort(404);
        }

        if (!$authUser?->hasStoreRole('owner', 'admin', 'seller')) {
            abort(403, 'No autorizado para emitir la guía de despacho fiscal.');
        }

        $order->loadMissing(['tenant', 'user', 'details.variant.product', 'details.taxes', 'payments.payment']);

        if (!(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)) {
            return back()->with('error', 'La facturación digital está desactivada para esta tienda.');
        }

        $service = app(TheFactoryHkaService::class);
        if (!$service->isConfigured()) {
            return back()->with('error', 'La integración de facturación digital no está configurada en el servidor.');
        }

        $hasApprovedPayments = $order->payments->contains(fn (Payment $payment) => (int) $payment->status === 1);
        if (!$hasApprovedPayments && (int) $order->status !== 1) {
            return back()->with('error', 'Para emitir la guía fiscal la venta debe estar finalizada o tener pagos aprobados.');
        }

        try {
            $this->emitDispatchGuideIfNeeded($order, $service);
            $latestGuide = $this->resolveLatestDispatchGuideDocument($order->fresh('electronicDocuments'));

            if (!$latestGuide || !$latestGuide->issued_at) {
                return back()->with('error', 'No fue posible emitir la guía de despacho fiscal en HKA.' . (!empty($latestGuide?->mensaje) ? ' Detalle: ' . $latestGuide->mensaje : ''));
            }

            return back()->with('success', 'Guía de despacho fiscal emitida correctamente en HKA.');
        } catch (\Throwable $exception) {
            return back()->with('error', 'No fue posible emitir la guía de despacho fiscal en HKA: ' . $exception->getMessage());
        }
    }

    public function orderDeliverToggleStatus($id, Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller', 'warehouse', 'delivery')) {
            return response()->json(['message' => 'No autorizado para cambiar el estado de entrega.'], 403);
        }

        $reason = null;
        if ((int) $request->status === 2) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para revertir la entrega.');
        }

        // Recuperar la orden con sus relaciones
        $order = SalesOrder::with([
            'user', 
            'tenant',
            'details', 
            'details.variant.product', 
            'payments.payment'
        ])->findOrFail($id);

        if ((int) ($order->tenant_id ?? 0) !== (int) ($user->tenant_id ?? 0)) {
            abort(404);
        }

        $tenantPlanCapabilities = TenantPlanCapabilities::forTenant($order->tenant);
        if (!$tenantPlanCapabilities->allowsDeliveryOperations() && $this->orderUsesDeliveryOperations($order)) {
            return response()->json([
                'message' => 'El plan Free no permite gestionar cambios de estado para delivery o envíos heredados.'
            ], 403);
        }
    
        // Actualizar el estado de la orden
        $order->deliver_status = $request->status;
        $order->save();

        if ((int) $order->deliver_status === 2) {
            ActionReason::log('sales_orders', 'DELIVERY_REVERTED', (string) $reason, [
                'order_id' => $order->id,
                'tenant_id' => $order->tenant_id,
            ]);
        }

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

        $reason = null;
        if ((int) $request->status === 3) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para marcar el pago como rechazado o pendiente.');
        }

        // Buscar el pago
        $payment = Payment::findOrFail($id);
        // Cambiar el estado del pago
        $payment->status = $request->status;
        $payment->save();
        $payment->loadMissing(['salesOrder.user']);

        if ($payment->salesOrder) {
            $payment->salesOrder->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);
            $this->syncSellerCommissionForOrder($payment->salesOrder);
        }

        if ((int) $payment->status === 3) {
            ActionReason::log('payments', 'PAYMENT_REVERTED', (string) $reason, [
                'payment_id' => $payment->id,
                'order_id' => $payment->sales_order_id,
            ]);
        }

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

                $payment->salesOrder->loadMissing('tenant');
                if ($payment->salesOrder->tenant) {
                    $this->notifyDeliveryTeamIfEnabled($payment->salesOrder->tenant, $payment->salesOrder, 'El pedido #' . $orderId . ' tiene pago aprobado y está listo para gestionar entrega.');
                }
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

    public function updatePaymentEntry($id, Request $request)
    {
        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['success' => false, 'message' => 'No autorizado para editar pagos.'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $payment = Payment::with(['salesOrder', 'images'])->findOrFail($id);

        if ((int) ($payment->status ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Los pagos aprobados no se pueden editar.',
            ], 422);
        }

        $updatedAmount = round((float) ($validated['amount'] ?? 0), 2);
        $existingRateToBase = (float) ($payment->exchange_rate_to_base ?? 0);
        $paymentCurrency = strtoupper(trim((string) ($payment->currency ?? '')));

        $payment->amount = $updatedAmount;
        $payment->amount_base = $updatedAmount;
        if ($paymentCurrency === 'BS' || $paymentCurrency === 'VES') {
            $payment->amount_original = $existingRateToBase > 0
                ? round($updatedAmount / $existingRateToBase, 2)
                : round((float) ($payment->amount_original ?? $updatedAmount), 2);
            $payment->exchange_rate_to_base = $existingRateToBase > 0 ? $existingRateToBase : null;
        } else {
            $payment->amount_original = $updatedAmount;
            $payment->exchange_rate_to_base = 1;
        }
        $payment->reference = trim((string) ($validated['reference'] ?? '')) ?: null;
        $payment->save();

        if ($request->hasFile('proof_image')) {
            foreach ($payment->images as $existingImage) {
                $existingPath = trim((string) ($existingImage->image_path ?? ''));
                if ($existingPath !== '') {
                    ImageStorage::deleteIfExists($existingPath);
                }
                $existingImage->delete();
            }

            $proofPath = ImageStorage::storeUploadedFile($request->file('proof_image'), 'payment_proofs');
            if (!empty($proofPath)) {
                PaymentImage::create([
                    'payment_id' => $payment->id,
                    'image_path' => $proofPath,
                ]);
            }
        }

        if ($payment->salesOrder) {
            $payment->salesOrder->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);
            $this->syncSellerCommissionForOrder($payment->salesOrder);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago actualizado correctamente.',
        ]);
    }

    public function deletePaymentEntry($id)
    {
        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['success' => false, 'message' => 'No autorizado para eliminar pagos.'], 403);
        }

        $payment = Payment::with(['salesOrder', 'images'])->findOrFail($id);

        if ((int) ($payment->status ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Los pagos aprobados no se pueden eliminar.',
            ], 422);
        }

        foreach ($payment->images as $existingImage) {
            $existingPath = trim((string) ($existingImage->image_path ?? ''));
            if ($existingPath !== '') {
                ImageStorage::deleteIfExists($existingPath);
            }
            $existingImage->delete();
        }

        $order = $payment->salesOrder;
        $payment->delete();

        if ($order) {
            $order->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);
            $this->syncSellerCommissionForOrder($order);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago eliminado correctamente.',
        ]);
    }

    private function attemptElectronicEmission(SalesOrder $order): void
    {
        try {
            $order->loadMissing(['payments', 'details', 'details.variant.product', 'details.taxes', 'tenant', 'user']);

            if (!(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)) {
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

            $this->emitDispatchGuideIfNeeded($order, $service);

            if (($order->document_issue_mode ?? 'delivery_note') !== 'electronic_invoice') {
                return;
            }

            $alreadyIssuedInvoice = ElectronicDocument::query()
                ->where('sales_order_id', $order->id)
                ->where('tipo_documento', '01')
                ->whereNotNull('issued_at')
                ->where('is_annulled', false)
                ->exists();

            if ($alreadyIssuedInvoice) {
                return;
            }

            $payload = $service->buildInvoicePayloadFromOrder($order);
            $response = $service->emitDocument($payload);
            $internalNumber = app(FiscalCorrelativeService::class)->next((int) $order->tenant_id, 'invoice', 'FAC');

            $document = ElectronicDocument::create([
                'tenant_id' => $order->tenant_id,
                'sales_order_id' => $order->id,
                'created_by' => auth()->id(),
                'provider' => 'thefactoryhka',
                'tipo_documento' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.tipoDocumento', '01'),
                'serie' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.serie', ''),
                'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', Arr::get($payload, 'encabezado.identificacionDocumento.numeroDocumento')),
                'internal_number' => $internalNumber,
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

            if ($response['ok'] ?? false) {
                ActionReason::log('electronic_invoices', 'INVOICE_CREATED', 'Factura electrónica emitida automáticamente', [
                    'sales_order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'electronic_document_id' => $document->id,
                    'numero_documento' => $document->numero_documento,
                    'numero_control' => $document->numero_control,
                ]);

                Log::info('Factura electrónica creada', [
                    'sales_order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'electronic_document_id' => $document->id,
                    'numero_documento' => $document->numero_documento,
                    'numero_control' => $document->numero_control,
                    'transaccion_id' => $document->transaccion_id,
                    'created_by' => auth()->id(),
                    'source' => 'automatic_emit',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo emitir factura electrónica automáticamente', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function emitDispatchGuideIfNeeded(SalesOrder $order, TheFactoryHkaService $service): void
    {
        $alreadyIssuedGuide = ElectronicDocument::query()
            ->where('sales_order_id', $order->id)
            ->whereIn('tipo_documento', ['04', '06'])
            ->whereNotNull('issued_at')
            ->where('is_annulled', false)
            ->exists();

        if ($alreadyIssuedGuide) {
            return;
        }

        $payload = $service->buildInvoicePayloadFromOrder($order, [
            'tipo_documento' => '04',
            'tipo_de_venta' => 'Interna',
        ]);

        $response = $service->emitDocument($payload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $diagnosticMessage = $this->extractIntegrationDiagnosticMessage($response);
        $internalNumber = app(FiscalCorrelativeService::class)->next((int) $order->tenant_id, 'dispatch_guide', 'GDE');

        ElectronicDocument::create([
            'tenant_id' => $order->tenant_id,
            'sales_order_id' => $order->id,
            'created_by' => auth()->id(),
            'provider' => 'thefactoryhka',
            'tipo_documento' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.tipoDocumento', '04'),
            'serie' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.serie', ''),
            'numero_documento' => (string) Arr::get($responseData, 'resultado.numeroDocumento', Arr::get($payload, 'encabezado.identificacionDocumento.numeroDocumento')),
            'internal_number' => $internalNumber,
            'numero_control' => (string) Arr::get($responseData, 'resultado.numeroControl', ''),
            'transaccion_id' => (string) Arr::get($responseData, 'resultado.transaccionId', Arr::get($payload, 'encabezado.identificacionDocumento.transaccionId')),
            'estado_documento' => (string) Arr::get($responseData, 'resultado.autorizado', Arr::get($responseData, 'resultado.imprentaDigital', '')),
            'codigo' => (string) Arr::get($responseData, 'codigo', ''),
            'mensaje' => $diagnosticMessage,
            'url_consulta' => (string) Arr::get($responseData, 'resultado.urlConsulta', ''),
            'request_payload' => $payload,
            'response_payload' => !empty($responseData) ? $responseData : [
                'status' => $response['status'] ?? null,
                'message' => $diagnosticMessage,
                'raw' => $response['raw'] ?? null,
            ],
            'issued_at' => ($response['ok'] ?? false) ? now() : null,
        ]);
    }

    private function extractIntegrationDiagnosticMessage(array $response): string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $parts = [];

        $mainMessage = trim((string) ($response['message'] ?? ''));
        if ($mainMessage !== '') {
            $parts[] = $mainMessage;
        }

        $providerMessage = trim((string) Arr::get($data, 'mensaje', Arr::get($data, 'Mensaje', '')));
        if ($providerMessage !== '' && !in_array($providerMessage, $parts, true)) {
            $parts[] = $providerMessage;
        }

        $validations = collect(Arr::get($data, 'validaciones', []))
            ->filter(fn ($validation) => is_scalar($validation) && trim((string) $validation) !== '')
            ->map(fn ($validation) => trim((string) $validation))
            ->values()
            ->all();

        if (!empty($validations)) {
            $parts[] = implode(' | ', $validations);
        }

        $code = trim((string) Arr::get($data, 'codigo', Arr::get($data, 'Codigo', '')));
        if ($code !== '') {
            $parts[] = 'Código HKA: ' . $code . '.';
        }

        if (empty($parts)) {
            $raw = trim((string) ($response['raw'] ?? ''));
            if ($raw !== '') {
                return Str::limit($raw, 300);
            }

            return 'Error de integración';
        }

        return implode(' ', $parts);
    }

    private function resolvePrimaryInvoiceDocument(SalesOrder $order): ?ElectronicDocument
    {
        $documents = $order->relationLoaded('electronicDocuments')
            ? $order->electronicDocuments
            : $order->electronicDocuments()->get();

        $invoice = $documents
            ->where('tipo_documento', '01')
            ->sortByDesc('id')
            ->first();

        return $invoice instanceof ElectronicDocument ? $invoice : null;
    }

    private function resolveLatestDispatchGuideDocument(SalesOrder $order): ?ElectronicDocument
    {
        $documents = $order->relationLoaded('electronicDocuments')
            ? $order->electronicDocuments
            : $order->electronicDocuments()->get();

        $guides = $documents
            ->filter(fn (ElectronicDocument $document) => in_array((string) $document->tipo_documento, ['04', '06'], true))
            ->sortByDesc('id');

        $guide = $guides
            ->filter(fn (ElectronicDocument $document) => !is_null($document->issued_at) && !(bool) $document->is_annulled)
            ->first();

        if (!$guide) {
            $guide = $guides->first();
        }

        return $guide instanceof ElectronicDocument ? $guide : null;
    }

    private function buildPendingDispatchGuideAlertData($salesOrders, ?Tenant $tenant): array
    {
        $pendingOrders = collect($salesOrders)
            ->map(function (SalesOrder $order) {
                $guide = $this->resolveLatestDispatchGuideDocument($order);
                $invoice = $this->resolvePrimaryInvoiceDocument($order);
                $hasActiveGuide = $guide && !is_null($guide->issued_at) && !(bool) $guide->is_annulled;
                $hasActiveInvoice = $invoice && !is_null($invoice->issued_at) && !(bool) $invoice->is_annulled;

                if (!$hasActiveGuide || $hasActiveInvoice) {
                    return null;
                }

                $order->latest_dispatch_guide_document = $guide;

                return $order;
            })
            ->filter()
            ->values();

        $now = now();
        $fortnightStart = $now->day <= 15 ? $now->copy()->startOfMonth() : $now->copy()->day(16)->startOfDay();
        $fortnightEnd = $now->day <= 15 ? $now->copy()->day(15)->endOfDay() : $now->copy()->endOfMonth();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $fortnightCount = $pendingOrders->filter(function (SalesOrder $order) use ($fortnightStart, $fortnightEnd) {
            $issuedAt = optional($order->latest_dispatch_guide_document)->issued_at;
            return $issuedAt && $issuedAt->between($fortnightStart, $fortnightEnd);
        })->count();

        $monthCount = $pendingOrders->filter(function (SalesOrder $order) use ($monthStart, $monthEnd) {
            $issuedAt = optional($order->latest_dispatch_guide_document)->issued_at;
            return $issuedAt && $issuedAt->between($monthStart, $monthEnd);
        })->count();

        return [
            'total_count' => (int) $pendingOrders->count(),
            'fortnight_count' => (int) $fortnightCount,
            'monthly_count' => (int) $monthCount,
            'default_email' => trim((string) ($tenant?->email ?? auth()->user()?->email ?? '')),
        ];
    }

    private function buildPendingDispatchGuidesPeriodReport(int $tenantId, string $period): array
    {
        $now = now();
        $start = $period === 'fortnight'
            ? ($now->day <= 15 ? $now->copy()->startOfMonth() : $now->copy()->day(16)->startOfDay())
            : $now->copy()->startOfMonth();
        $end = $period === 'fortnight'
            ? ($now->day <= 15 ? $now->copy()->day(15)->endOfDay() : $now->copy()->endOfMonth())
            : $now->copy()->endOfMonth();

        $orders = SalesOrder::with(['user', 'electronicDocuments'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $guide = $this->resolveLatestDispatchGuideDocument($order);
                $invoice = $this->resolvePrimaryInvoiceDocument($order);
                $hasActiveGuide = $guide && !is_null($guide->issued_at) && !(bool) $guide->is_annulled;
                $hasActiveInvoice = $invoice && !is_null($invoice->issued_at) && !(bool) $invoice->is_annulled;

                if (!$hasActiveGuide || $hasActiveInvoice) {
                    return null;
                }

                return [
                    'order_id' => (int) $order->id,
                    'customer_name' => (string) ($order->user->name ?? 'N/A'),
                    'sale_date' => (string) ($order->date ?? ''),
                    'delivery_type' => (string) ($order->preference ?? ''),
                    'guide_number' => (string) ($guide->numero_documento ?? ''),
                    'guide_control' => (string) ($guide->numero_control ?? ''),
                    'guide_issued_at' => optional($guide->issued_at),
                ];
            })
            ->filter(function (?array $row) use ($start, $end) {
                return $row && $row['guide_issued_at'] && $row['guide_issued_at']->between($start, $end);
            })
            ->values()
            ->map(function (array $row) {
                $row['guide_issued_at_display'] = optional($row['guide_issued_at'])->format('d/m/Y H:i');
                unset($row['guide_issued_at']);
                return $row;
            });

        return [
            'period' => $period,
            'label' => $period === 'fortnight' ? 'quincenal' : 'mensual',
            'start_date' => $start->format('d/m/Y'),
            'end_date' => $end->format('d/m/Y'),
            'count' => (int) $orders->count(),
            'orders' => $orders->all(),
        ];
    }

    private function resolveInternalDispatchFilename(int $orderId): string
    {
        return 'depachointernoshpx' . $orderId . '.pdf';
    }

    private function resolveTenantBillingLogoDataUri(?Tenant $tenant): ?string
    {
        $fallbackPath = public_path('assets/img/shopix5.png');
        $fallbackDataUri = $this->buildDataUriFromPath($fallbackPath);

        if (!$tenant || (empty($tenant->billing_logo) && empty($tenant->logo))) {
            return $fallbackDataUri;
        }

        $logoPath = trim((string) ($tenant->billing_logo ?: $tenant->logo));
        if ($logoPath === '') {
            return $fallbackDataUri;
        }

        try {
            if (ImageStorage::isGooglePath($logoPath)) {
                $googleFileId = ImageStorage::extractGoogleFileId($logoPath);
                if ($googleFileId !== '') {
                    $file = ImageStorage::downloadGoogleFileById($googleFileId, 5);
                    $content = (string) ($file['content'] ?? '');
                    $mime = trim((string) ($file['mime_type'] ?? 'image/png'));

                    if ($content !== '' && $this->isSupportedBillingLogoMime($mime)) {
                        return 'data:' . ($mime !== '' ? $mime : 'image/png') . ';base64,' . base64_encode($content);
                    }
                }
            }

            if (Storage::disk('public')->exists($logoPath)) {
                $content = Storage::disk('public')->get($logoPath);
                $mime = (string) (Storage::disk('public')->mimeType($logoPath) ?: 'image/png');

                if ($content !== '' && $this->isSupportedBillingLogoMime($mime)) {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }

            $publicPath = public_path(ltrim($logoPath, '/'));
            if (is_file($publicPath)) {
                return $this->buildDataUriFromPath($publicPath) ?: $fallbackDataUri;
            }

            $storagePublicPath = public_path('storage/' . ltrim($logoPath, '/'));
            if (is_file($storagePublicPath)) {
                return $this->buildDataUriFromPath($storagePublicPath) ?: $fallbackDataUri;
            }
        } catch (\Throwable $exception) {
            // Silently fallback to default logo for PDF rendering stability.
        }

        return $fallbackDataUri;
    }

    private function buildDataUriFromPath(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        if (!$this->isSupportedBillingLogoMime($mime)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function isSupportedBillingLogoMime(string $mime): bool
    {
        $normalized = Str::lower(trim($mime));

        return in_array($normalized, [
            'image/png',
            'image/jpg',
            'image/jpeg',
            'image/svg+xml',
            'text/svg',
            'image/webp',
        ], true);
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

    private function resolveSalesRepresentativeId(): ?int
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return null;
        }

        if ($authUser->hasStoreRole('owner', 'admin', 'seller')) {
            return (int) $authUser->id;
        }

        return null;
    }

    private function syncSellerCommissionForOrder(SalesOrder $order): void
    {
        $order->loadMissing(['payments', 'details', 'returns.items', 'salesRepresentative']);

        $sellerId = (int) ($order->sales_rep_user_id ?? 0);
        if ($sellerId <= 0) {
            return;
        }

        $seller = $order->salesRepresentative;
        if (!$seller || !$seller->hasStoreRole('owner', 'admin', 'seller')) {
            return;
        }

        $commissionRate = max(0, min(100, (float) ($seller->commission_percentage ?? 0)));
        $approvedPaid = (float) $order->payments->where('status', 1)->sum('amount');
        $returnsTotal = (float) $order->returns->flatMap->items->sum(function ($item) {
            return (float) $item->price * (float) $item->quantity;
        });
        $netOrderTotal = (float) $order->totalAfterReturns($returnsTotal);
        $commissionBase = round(max(0, min($netOrderTotal, $approvedPaid)), 4);

        $existing = SellerCommission::query()
            ->where('tenant_id', (int) $order->tenant_id)
            ->where('sales_order_id', (int) $order->id)
            ->first();

        if ($commissionRate <= 0 || $commissionBase <= 0) {
            if ($existing && (string) ($existing->status ?? 'pending') !== 'paid') {
                $existing->delete();
            }

            return;
        }

        $commissionAmount = round(($commissionBase * $commissionRate) / 100, 4);

        if ($existing) {
            if ((string) ($existing->status ?? 'pending') === 'paid') {
                return;
            }

            $existing->update([
                'seller_user_id' => $sellerId,
                'commission_base_amount' => $commissionBase,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'currency_code' => (string) ($order->sale_currency_code ?? 'USD'),
                'status' => 'pending',
                'calculated_at' => now(),
            ]);

            return;
        }

        SellerCommission::create([
            'tenant_id' => (int) $order->tenant_id,
            'seller_user_id' => $sellerId,
            'sales_order_id' => (int) $order->id,
            'commission_base_amount' => $commissionBase,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'currency_code' => (string) ($order->sale_currency_code ?? 'USD'),
            'status' => 'pending',
            'calculated_at' => now(),
            'created_by' => auth()->id(),
        ]);
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

    private function notifyDeliveryTeamIfEnabled(Tenant $tenant, SalesOrder $order, string $message): void
    {
        $settings = DeliveryManager::settings($tenant);

        if (!$settings['notifications_enabled']) {
            return;
        }

        WorkflowNotifier::notifyTenantRoles((int) $tenant->id, ['almacen', 'delivery'], [
            'title' => 'Pedido listo para entrega',
            'message' => $message,
            'type' => 'delivery-pending',
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'action' => 'prepare_delivery',
        ]);
    }

    private function extractDeliveryOrderMeta(SalesOrder $order): array
    {
        $address = trim((string) ($order->address ?? ''));
        $receiverName = null;
        $receiverPhone = null;
        $extraInfo = [];

        foreach (preg_split('/\r\n|\r|\n/', $address) ?: [] as $line) {
            $normalizedLine = trim((string) $line);
            if ($normalizedLine === '') {
                continue;
            }

            if (preg_match('/^Recibe:\s*(.+)$/iu', $normalizedLine, $matches) === 1) {
                $receiverName = trim((string) ($matches[1] ?? ''));
                continue;
            }

            if (preg_match('/^Tel[eé]fono(?:\s+receptor)?:\s*(.+)$/iu', $normalizedLine, $matches) === 1) {
                $receiverPhone = trim((string) ($matches[1] ?? ''));
                continue;
            }

            if (preg_match('/^Coordenadas?:/iu', $normalizedLine) === 1) {
                continue;
            }

            $extraInfo[] = $normalizedLine;
        }

        $hasCoordinates = !is_null($order->delivery_latitude) && !is_null($order->delivery_longitude);
        $mapUrl = $hasCoordinates
            ? 'https://www.google.com/maps?q=' . $order->delivery_latitude . ',' . $order->delivery_longitude
            : null;

        return [
            'receiver_name' => $receiverName,
            'receiver_phone' => $receiverPhone,
            'extra_info' => trim(implode(' | ', $extraInfo)),
            'destination_label' => $hasCoordinates
                ? 'Ubicación exacta registrada en mapa'
                : ($address !== '' ? $address : 'Sin destino registrado'),
            'map_url' => $mapUrl,
        ];
    }

    private function orderUsesDeliveryOperations(SalesOrder $order): bool
    {
        $preference = mb_strtolower(trim((string) ($order->preference ?? '')));

        return str_contains($preference, 'delivery')
            || str_contains($preference, 'env')
            || str_contains($preference, 'shipping');
    }

    public function processReturn(Request $request, $orderId)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = auth()->user();
        if (!$user?->hasStoreRole('owner', 'admin', 'seller')) {
            return response()->json(['message' => 'No autorizado para registrar devoluciones.'], 403);
        }

        $order = SalesOrder::with(['details.taxes'])->findOrFail($orderId);
        $itemsToReturn = $request->input('items'); // array de items con id y cantidad
        $reason = ActionReason::require($request, 'reason', 'Debes indicar el motivo de la devolucion.');

        if (empty($itemsToReturn)) {
            return response()->json(['error' => 'No se especificaron productos a devolver.'], 400);
        }

        $return = SalesReturn::create([
            'sales_order_id' => $order->id,
            'reason' => $reason,
        ]);

        $returnSubtotal = 0.0;
        $returnTaxTotal = 0.0;

        foreach ($itemsToReturn as $item) {
            $quantityToReturn = (float) ($item['quantity'] ?? 0);
            $disposition = strtolower(trim((string) ($item['disposition'] ?? 'resalable')));
            if (!in_array($disposition, ['resalable', 'damaged', 'no_physical_return'], true)) {
                return response()->json(['error' => 'Disposición de inventario inválida.'], 422);
            }

            $detail = $order->details->where('product_variant_id', $item['id'])->first();

            if (!$detail || $quantityToReturn <= 0 || $quantityToReturn > (float) $detail->quantity) {
                return response()->json(['error' => 'Cantidad inválida para devolver.'], 400);
            }

            $detailQuantity = max(1, (float) $detail->quantity);
            $lineSubtotal = round((float) $detail->price * $quantityToReturn, 2);
            $lineTaxTotal = (float) $detail->taxes->sum('tax_amount');
            $lineTaxReturned = round($lineTaxTotal * ($quantityToReturn / $detailQuantity), 2);
            $returnSubtotal += $lineSubtotal;
            $returnTaxTotal += $lineTaxReturned;

            // Registrar item devuelto
            SalesReturnItem::create([
                'sales_return_id' => $return->id,
                'product_variant_id' => $item['id'],
                'quantity' => $quantityToReturn,
                'price' => $detail->price,
                'disposition' => $disposition,
                'returned_subtotal' => $lineSubtotal,
                'returned_tax_amount' => $lineTaxReturned,
                'returned_igtf_amount' => 0,
            ]);

            // Actualizar inventario solo si la mercancía vuelve apta para venta
            if ($disposition === 'resalable') {
                $variant = ProductVariant::find($item['id']);
                if ($variant) {
                    $variant->stock += $quantityToReturn;
                    $variant->save();
                }
            }
        }

        $orderTaxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $orderTotalWithoutIgtf = round((float) $order->gross_total + $orderTaxTotal, 2);
        $orderIgtfAmount = round((float) ($order->igtf_amount ?? 0), 2);
        $returnGrossBeforeIgtf = round($returnSubtotal + $returnTaxTotal, 2);
        $returnIgtf = $orderTotalWithoutIgtf > 0
            ? round($orderIgtfAmount * ($returnGrossBeforeIgtf / $orderTotalWithoutIgtf), 2)
            : 0.0;

        if ($returnIgtf > 0) {
            $remainingIgtf = $returnIgtf;
            $returnItems = $return->items()->get();
            $lastIndex = max(0, $returnItems->count() - 1);

            foreach ($returnItems as $index => $returnItem) {
                $lineSubtotal = (float) ($returnItem->returned_subtotal ?? 0);
                $lineTax = (float) ($returnItem->returned_tax_amount ?? 0);
                $lineGross = round($lineSubtotal + $lineTax, 2);

                if ($index === $lastIndex) {
                    $allocatedIgtf = round(max(0, $remainingIgtf), 2);
                } else {
                    $weight = $returnGrossBeforeIgtf > 0 ? ($lineGross / $returnGrossBeforeIgtf) : 0;
                    $allocatedIgtf = round($returnIgtf * $weight, 2);
                    $remainingIgtf = round($remainingIgtf - $allocatedIgtf, 2);
                }

                $returnItem->returned_igtf_amount = $allocatedIgtf;
                $returnItem->save();
            }
        }

        $return->subtotal_returned = round($returnSubtotal, 2);
        $return->tax_returned = round($returnTaxTotal, 2);
        $return->igtf_returned = round(max(0, $returnIgtf), 2);
        $return->total_returned = round($returnSubtotal + $returnTaxTotal + max(0, $returnIgtf), 2);
        $return->save();

        // Marcar la orden como que tiene devolución
        $order->has_returns = true;
        $order->save();

        ActionReason::log('sales_returns', 'RETURN_CREATED', $reason, [
            'sales_order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
        ]);

        return response()->json(['message' => 'Devolución registrada exitosamente.']);
    }

}
