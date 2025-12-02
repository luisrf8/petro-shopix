<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    /** Mostrar vista con impuestos */
    public function index()
    {
        $taxes = Tax::all();
        return view('taxes', compact('taxes'));
    }

    /** Crear impuesto */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:taxes,code',
            'name' => 'required',
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable'
        ]);

        $tax = Tax::create([
            'code' => strtoupper($request->code),
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
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable'
        ]);

        // Validar cambio de código
        if ($request->code && $request->code !== $tax->code) {
            $request->validate([
                'code' => 'required|unique:taxes,code'
            ]);
        }

        $oldData = $tax->toArray(); // Guardar datos antiguos para el log

        $tax->update([
            'code' => strtoupper($request->code ?? $tax->code),
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
            'is_active' => 'required|in:0,1'
        ]);

        $oldStatus = $tax->is_active;
        $tax->update(['is_active' => $request->is_active]);

        $userId = auth()->id();
        $description = "Cambio estado de impuesto {$tax->name} ({$tax->code}) de " 
                        . ($oldStatus ? 'Activo' : 'Inactivo') 
                        . " a " . ($tax->is_active ? 'Activo' : 'Inactivo');
                        DB::statement("CALL log_change(?, ?, ?, ?)", ['taxes', 'update', $userId, $description]);

        return response()->json(['message' => 'Estado de impuesto actualizado'], 200);
    }
}
