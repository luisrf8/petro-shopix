<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use App\Support\ActionReason;
use Illuminate\Support\Str;

class TaxController extends Controller
{
    /** Mostrar vista con impuestos */
    public function index()
    {
        $taxes = Tax::query()->orderBy('name')->get();
        return view('taxes', compact('taxes'));
    }

    /** Crear impuesto */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:20|unique:taxes,code',
            'name' => 'required',
            'rate' => 'required|numeric|gte:0',
            'description' => 'nullable'
        ]);

        $rate = round((float) $request->input('rate', 0), 2);
        if (!$this->isValidFiscalTaxRate((string) $request->input('name', ''), (string) $request->input('code', ''), $rate)) {
            return response()->json([
                'message' => 'Tasa fiscal inválida. IVA solo permite 0%, 8%, 16% o 31%; IGTF solo permite 3%.',
            ], 422);
        }

        $code = $this->resolveTaxCode((string) $request->input('code', ''), (string) $request->input('name', ''));

        $tax = Tax::create([
            'code' => $code,
            'name' => $request->name,
            'rate' => $rate,
            'description' => $request->description,
        ]);

        $userId = auth()->id();
        $description = "Creó impuesto: {$tax->name} ({$tax->code}) con tasa {$tax->rate}";
        DB::statement("CALL log_change(?, ?, ?, ?)", ['taxes', 'insert', $userId, $description]);

        return response()->json(['message' => 'Impuesto creado correctamente'], 201);
    }

    /** Actualizar impuesto */
    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'name' => 'required',
            'rate' => 'required|numeric|gte:0',
            'description' => 'nullable'
        ]);

        $rate = round((float) $request->input('rate', 0), 2);
        if (!$this->isValidFiscalTaxRate((string) $request->input('name', $tax->name), (string) $request->input('code', $tax->code), $rate)) {
            return response()->json([
                'message' => 'Tasa fiscal inválida. IVA solo permite 0%, 8%, 16% o 31%; IGTF solo permite 3%.',
            ], 422);
        }

        $oldData = $tax->toArray(); // Guardar datos antiguos para el log
        $code = $this->resolveTaxCode((string) $request->input('code', $tax->code), (string) $request->input('name', $tax->name), (int) $tax->id);

        $tax->update([
            'code' => $code,
            'name' => $request->name,
            'rate' => $rate,
            'description' => $request->description,
        ]);

        $userId = auth()->id();
        $description = "Actualizó impuesto: {$oldData['name']} ({$oldData['code']}) -> {$tax->name} ({$tax->code}), tasa {$tax->rate}";
        DB::statement("CALL log_change(?, ?, ?, ?)", ['taxes', 'update', $userId, $description]);

        return response()->json(['message' => 'Impuesto actualizado correctamente'], 200);
    }

    /** Activar / Inactivar impuesto */
    public function toggleStatus(Request $request, Tax $tax)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $oldStatus = (bool) $tax->is_active;
        $reason = null;
        if ($oldStatus && !$request->boolean('is_active')) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar el impuesto.');
        }

        $tax->update(['is_active' => (bool) $request->boolean('is_active')]);

        $userId = auth()->id();
        $description = "Cambio estado de impuesto {$tax->name} ({$tax->code}) de " 
                        . ($oldStatus ? 'Activo' : 'Inactivo') 
                        . " a " . ($tax->is_active ? 'Activo' : 'Inactivo');
                        DB::statement("CALL log_change(?, ?, ?, ?)", ['taxes', 'update', $userId, $description]);

        if ($oldStatus && !(bool) $tax->is_active) {
            ActionReason::log('taxes', 'TAX_DEACTIVATED', (string) $reason, [
                'tax_id' => $tax->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado de impuesto actualizado',
            'is_active' => (bool) $tax->is_active,
        ], 200);
    }

    private function resolveTaxCode(string $requestedCode, string $name, ?int $ignoreTaxId = null): string
    {
        $baseCode = strtoupper(trim($requestedCode));

        if ($baseCode === '') {
            $transliterated = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) : $name;
            $baseCode = strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '', (string) $transliterated));
        }

        if ($baseCode === '') {
            $baseCode = 'TAX';
        }

        $candidate = substr($baseCode, 0, 20);
        $suffix = 1;

        while (
            Tax::query()
                ->when($ignoreTaxId, fn ($query) => $query->where('id', '!=', $ignoreTaxId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $suffixText = (string) $suffix;
            $candidate = substr($baseCode, 0, max(0, 20 - strlen($suffixText))) . $suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function isValidFiscalTaxRate(string $name, string $code, float $rate): bool
    {
        if ($this->looksLikeIgtfTax($name, $code)) {
            return abs($rate - 3.0) < 0.0001;
        }

        if ($this->looksLikeIvaTax($name, $code)) {
            return in_array(round($rate, 2), [0.0, 8.0, 16.0, 31.0], true);
        }

        return true;
    }

    private function looksLikeIvaTax(string $name, string $code): bool
    {
        $haystack = Str::lower(Str::ascii(trim($name . ' ' . $code)));

        return str_contains($haystack, 'iva') || str_contains($haystack, 'exent');
    }

    private function looksLikeIgtfTax(string $name, string $code): bool
    {
        $haystack = Str::lower(Str::ascii(trim($name . ' ' . $code)));

        return str_contains($haystack, 'igtf');
    }
}
