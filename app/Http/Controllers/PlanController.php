<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ImageStorage;

class PlanController extends Controller
{
    private function normalizeFeatures($features): array
    {
        if (is_array($features)) {
            return array_values(array_filter(array_map(function ($item) {
                return trim((string) $item);
            }, $features), function ($item) {
                return $item !== '';
            }));
        }

        if (is_string($features)) {
            return array_values(array_filter(array_map('trim', explode(',', $features)), function ($item) {
                return $item !== '';
            }));
        }

        return [];
    }

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
            'features'      => 'nullable',
            'status'        => 'nullable|in:0,1',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $validated['features'] = $this->normalizeFeatures($request->input('features'));

        // ✅ Manejo de imagen
        if ($request->hasFile('image')) {
            $path = ImageStorage::storeUploadedFile($request->file('image'), 'plans');
            $validated['image'] = $path;
        }

        $validated['status'] = $request->input('status', 1);

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

        $validated['features'] = $this->normalizeFeatures($request->input('features'));
        // ✅ Manejo de imagen (reemplazar si llega nueva)
        if ($request->hasFile('image')) {
            $storedImage = $plan->getRawOriginal('image');
            if (!empty($storedImage)) {
                ImageStorage::delete($storedImage);
            }

            $path = ImageStorage::storeUploadedFile($request->file('image'), 'plans');
            $validated['image'] = $path;
        }

        $validated['status'] = $request->input('status', $plan->status ?? 1);

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy($id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $plan = Plan::findOrFail($id);

        $storedImage = $plan->getRawOriginal('image');
        if (!empty($storedImage)) {
            ImageStorage::delete($storedImage);
        }

        $plan->delete();

        return response()->noContent();
    }
}