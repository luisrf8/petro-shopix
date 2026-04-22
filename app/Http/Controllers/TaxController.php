<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use App\Support\ActionReason;

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
            'rate' => 'required|numeric|gt:0',
            'description' => 'nullable'
        ]);

        $code = $this->resolveTaxCode((string) $request->input('code', ''), (string) $request->input('name', ''));

        $tax = Tax::create([
            'code' => $code,
            'name' => $request->name,
            'rate' => $request->rate,
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
            'rate' => 'required|numeric|gt:0',
            'description' => 'nullable'
        ]);

        $oldData = $tax->toArray(); // Guardar datos antiguos para el log
        $code = $this->resolveTaxCode((string) $request->input('code', $tax->code), (string) $request->input('name', $tax->name), (int) $tax->id);

        $tax->update([
            'code' => $code,
            'name' => $request->name,
            'rate' => $request->rate,
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
}
