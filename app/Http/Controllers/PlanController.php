<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        return view('plans', compact('plans'));
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'logo'          => 'nullable|string|max:500',
            'features'      => 'nullable|string',
            'status'        => 'nullable|in:0,1',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        // ✅ Normalizar `features` (siempre será JSON array)
        $validated['features'] = $validated['features']
            ? json_encode(array_map('trim', explode(',', $validated['features'])))
            : json_encode([]);

        // ✅ Manejo de imagen
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('plans', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $validated['status'] = $request->input('status', 0);

        $plan = Plan::create($validated);

        return response()->json($plan, 201);
    }

    public function update(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features'      => 'nullable',
            'status'        => 'nullable|in:0,1',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $validated['features'] = $validated['features']
            ? array_map('trim', explode(',', $validated['features']))
            : [];
        // ✅ Manejo de imagen (reemplazar si llega nueva)
        if ($request->hasFile('image')) {
            if ($plan->image && file_exists(public_path($plan->image))) {
                @unlink(public_path($plan->image));
            }

            $path = $request->file('image')->store('plans', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $validated['status'] = $request->input('status', $plan->status ?? 0);

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy($id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $plan = Plan::findOrFail($id);

        if ($plan->image && file_exists(public_path($plan->image))) {
            @unlink(public_path($plan->image));
        }

        $plan->delete();

        return response()->noContent();
    }
}