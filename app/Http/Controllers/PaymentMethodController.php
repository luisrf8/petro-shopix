<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\DollarRate;
use App\Models\EuroRate;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\ImageStorage;
use App\Support\AuditLogger;
use App\Support\ActionReason;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dólar', 'status' => true]);
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'status' => true]);

        $currencies = Currency::orderBy('name')->get();
        $paymentMethods = PaymentMethod::with('currency')
            ->where('tenant_id', $user->tenant_id)
            ->get();

        // Obtener el último valor de la tasa del dólar
        $dollarRate = DollarRate::where('tenant_id', $user->tenant_id)->latest('created_at')->first();
        $euroRate = EuroRate::where('tenant_id', $user->tenant_id)->latest('created_at')->first();
        $dollarRateHistory = DollarRate::where('tenant_id', $user->tenant_id)
            ->latest('date')
            ->latest('id')
            ->limit(50)
            ->get();
        $euroRateHistory = EuroRate::where('tenant_id', $user->tenant_id)
            ->latest('date')
            ->latest('id')
            ->limit(50)
            ->get();
        $baseCurrencyCode = strtoupper((string) optional(Tenant::find($user->tenant_id))->base_currency ?: 'USD');

        // Agrupar métodos de pago por moneda
        $groupedPaymentMethods = $paymentMethods->groupBy(function ($paymentMethod) {
            return $paymentMethod->currency->name; // Agrupar por el nombre de la moneda
        });

        return view('paymentMethods', compact('currencies', 'groupedPaymentMethods', 'dollarRate', 'euroRate', 'dollarRateHistory', 'euroRateHistory', 'baseCurrencyCode'));
    }
    // Crear un nuevo método de pago
    public function create(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'required|exists:currencies,id',
            'admin_name' => 'nullable|string',
            'dni' => 'nullable|string',
            'bank' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tenant_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $paymentMethod = PaymentMethod::create([
            'name' => $request->name,
            'currency_id' => $request->currency,
            'admin_name' => $request->admin_name,
            'description' => $request->description,
            'dni' => $request->dni,
            'bank' => $request->bank,
            'tenant_id' => $request->tenant_id
        ]);
        if ($request->hasFile('image')) {
            $path = ImageStorage::storeUploadedFile($request->file('image'), 'qr_images');

            // Convertir la ruta al formato requerido
            $formattedPath = json_encode([$path]);

            // Guardar la ruta en el campo correspondiente
            $paymentMethod->qr_image = $formattedPath;
            $paymentMethod->save();
        }

        return response()->json(['message' => 'Método de pago creado exitosamente', 'data' => $paymentMethod], 201);
    }

    public function currencyCreate(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tenant_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $currency = Currency::create([
            'name' => $request->name,
            'code' => $request->code,
            'tenant_id' => $request->tenant_id
        ]);

        return response()->json(['message' => 'Método de pago creado exitosamente', 'data' => $currency], 201);
    }
    public function edit(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        // Validar los datos del formulario
        $validated = $request->validate([
            'name' => 'required|string',
            'currency' => 'required|exists:currencies,id',
            'admin_name' => 'nullable|string',
            'dni' => 'nullable|string',
            'bank' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Buscar el método de pago
        $paymentMethod = PaymentMethod::findOrFail($id);
        $paymentMethod->update([
            'name' => $validated['name'],
            'currency_id' => $validated['currency'],
            'admin_name' => $validated['admin_name'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'bank' => $validated['bank'] ?? null,
        ]);

        // Procesar la imagen QR si se envía
        if ($request->hasFile('image')) {
            $path = ImageStorage::storeUploadedFile($request->file('image'), 'qr_images');

            // Convertir la ruta al formato requerido
            $formattedPath = json_encode([$path]);

            // Guardar la ruta en el campo correspondiente
            $paymentMethod->qr_image = $formattedPath;
            $paymentMethod->save();
        }

        // Respuesta con la ruta del QR
        return response()->json([
            'success' => true,
            'message' => 'Método de pago actualizado correctamente.',
            'method' => $paymentMethod->fresh('currency'),
            'qr_image' => $paymentMethod->qr_image,
        ]);
    }

    

    public function toggleStatus($id, Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        // Validar el parámetro de estado (is_active)
        // $validator = Validator::make($request->all(), [
        //     'is_active' => 'required|boolean',  // true para activar, false para inactivar
        // ]);
    
        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 400);
        // }
    
        // Buscar el método de pago
        $paymentMethod = PaymentMethod::findOrFail($id);
        $reason = null;
        if ((bool) $paymentMethod->status) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar el metodo de pago.');
        }
    
        // Actualizar el estado
        $paymentMethod->status = !$paymentMethod->status;
        $paymentMethod->save();

        if (!(bool) $paymentMethod->status) {
            ActionReason::log('payment_methods', 'PAYMENT_METHOD_DEACTIVATED', (string) $reason, [
                'payment_method_id' => $paymentMethod->id,
                'tenant_id' => $paymentMethod->tenant_id,
            ]);
        }
    
        // Responder con un mensaje de éxito
        $message = $request->is_active ? 'Método de pago activado exitosamente' : 'Método de pago inactivado exitosamente';
    
        return response()->json(['message' => $message], 200);
    }

    public function currencyToggleStatus($id, Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        // Buscar la moneda
        $currency = Currency::findOrFail($id);
        $reason = null;
        if ((bool) $currency->status) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar la moneda.');
        }
    
        // Cambiar el estado de la moneda
        $currency->status = !$currency->status;
        $currency->save();
    
        // Si la moneda se desactiva, desactivar también sus métodos de pago
        if ($currency->status == 0) {
            PaymentMethod::where('currency_id', $currency->id)->update(['status' => 0]);

            ActionReason::log('currencies', 'CURRENCY_DEACTIVATED', (string) $reason, [
                'currency_id' => $currency->id,
            ]);
        }
    
        // Responder con un mensaje de éxito
        $message = $currency->status ? 'Moneda activada exitosamente' : 'Moneda inactivada exitosamente, junto con sus métodos de pago.';
        
        return response()->json(['message' => $message], 200);
    }
    
    // Crear o editar una moneda
    public function updateCurrency(Currency $id, Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'name' => 'required|string|max:255|unique:currencies,name,' . $id->id,
            'code' => 'required|string',
        ]);

        $id->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);
        $id->refresh();

        return response()->json(['message' => 'Moneda actualizada o creada exitosamente', 'data' => $id], 200);
    }

    public function updateDollarRate(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'rate' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $tenantId = (int) $request->tenant_id;
        $previousRate = DollarRate::where('tenant_id', $tenantId)->latest('created_at')->value('rate');

        $rate = DollarRate::create([
            'tenant_id' => $tenantId,
            'rate' => $request->rate,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        AuditLogger::logEvent('paymentMethods', 'DOLLAR_RATE_UPDATED', 'Actualización de tasa del dólar.', (int) (auth()->id() ?? 0), [
            'tenant_id' => $tenantId,
            'previous_rate' => $previousRate,
            'new_rate' => (float) $rate->rate,
        ]);

        return response()->json([
            'message' => 'Tasa del dólar actualizada exitosamente',
            'data' => $rate
        ], 201);
    }

    public function getDollarRate()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $dollarRate = DollarRate::where('tenant_id', $tenantId)->latest('created_at')->first();
        return response()->json(['message' => 'Tasa del dólar obtenida exitosamente', 'data' => $dollarRate], 201);
    }

    public function updateEuroRate(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'rate' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $tenantId = (int) $request->tenant_id;
        $previousRate = EuroRate::where('tenant_id', $tenantId)->latest('created_at')->value('rate');

        $rate = EuroRate::create([
            'tenant_id' => $tenantId,
            'rate' => $request->rate,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        AuditLogger::logEvent('paymentMethods', 'EURO_RATE_UPDATED', 'Actualización de tasa del euro.', (int) (auth()->id() ?? 0), [
            'tenant_id' => $tenantId,
            'previous_rate' => $previousRate,
            'new_rate' => (float) $rate->rate,
        ]);

        return response()->json([
            'message' => 'Tasa del euro actualizada exitosamente',
            'data' => $rate
        ], 201);
    }

    public function updateTenantBaseCurrency(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'base_currency' => 'required|string|in:USD,EUR',
        ]);

        $tenant = Tenant::findOrFail((int) $validated['tenant_id']);
        $previousBaseCurrency = strtoupper((string) ($tenant->base_currency ?? 'USD'));
        $tenant->base_currency = strtoupper((string) $validated['base_currency']);
        $tenant->save();

        AuditLogger::logEvent('paymentMethods', 'BASE_CURRENCY_UPDATED', 'Actualización de moneda madre de la tienda.', (int) (auth()->id() ?? 0), [
            'tenant_id' => (int) $tenant->id,
            'previous_base_currency' => $previousBaseCurrency,
            'new_base_currency' => (string) $tenant->base_currency,
        ]);

        return response()->json([
            'message' => 'Moneda madre actualizada exitosamente',
            'data' => [
                'base_currency' => $tenant->base_currency,
            ],
        ], 200);
    }
    // Función para eliminar una imagen
    public function removeQrImage($methodId)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $paymentMethod = PaymentMethod::findOrFail($methodId);
    
        // Verificar si existe una imagen QR en el almacenamiento y eliminarla
        $qrImages = [];
        if (!empty($paymentMethod->qr_image) && is_string($paymentMethod->qr_image)) {
            $decoded = json_decode($paymentMethod->qr_image, true);
            if (is_array($decoded)) {
                $qrImages = $decoded;
            } else {
                $qrImages = [$paymentMethod->qr_image];
            }
        }

        foreach ($qrImages as $imagePath) {
            ImageStorage::delete($imagePath);
        }
    
        // Actualizar el campo `qr_image` a null
        $paymentMethod->qr_image = null;
        $paymentMethod->save();
    
        return response()->json(['success' => true, 'message' => 'QR eliminado correctamente.']);
    }

}
