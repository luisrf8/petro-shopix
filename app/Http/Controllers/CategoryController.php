<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Support\ImageStorage;
use App\Support\ActionReason;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $tenantId = $this->tenantScopeId(request());

        $categories = Category::with(['products' => function ($query) use ($tenantId) {
            $query->where('is_active', true)
                ->when($tenantId > 0, fn ($innerQuery) => $innerQuery->where('tenant_id', $tenantId))
                ->with(['variants']);
        }])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get();
    
        // Calcular total de stock por categoría
        foreach ($categories as $category) {
            $totalStock = 0;
    
            foreach ($category->products as $product) {
                foreach ($product->variants as $variant) {
                    $totalStock += $variant->stock;
                }
            }
    
            // Agregamos el total como propiedad adicional para usarlo en la vista
            $category->total_available_items = $totalStock;
        }
        return view('products.index', compact('categories'));
    }
    public function getCategories(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);

        if (!$tenantId || !is_numeric($tenantId)) {
            return response()->json(['error' => 'Tenant ID inválido o no enviado'], 400);
        }

        $categories = Category::where('tenant_id', $tenantId)->get();
        
        return response()->json($categories);
    }
    public function create()
    {
        return view('categories.create'); // Vista para crear una nueva categoría.
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenantId = $this->tenantWriteId($request);
        if ($tenantId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo identificar el tenant para crear la categoría.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg,webp|max:5120',
        ]);

        $imagePath = null;

        try {
            if ($request->hasFile('image')) {
                $imagePath = ImageStorage::storeUploadedFile($request->file('image'), 'categories/images');
            }

            $category = Category::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'tenant_id' => $tenantId,
                'image' => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente.',
                'category' => $category,
            ], 201);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                ImageStorage::delete($imagePath);
            }

            Log::error('Error al crear categoria', [
                'tenant_id' => $tenantId,
                'name' => $validated['name'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la categoría. Si adjuntaste una imagen, verifica el formato o intenta nuevamente.',
            ], 500);
        }
    }

    public function show(Category $category)
    {
        $this->abortIfCategoryOutOfTenant($category);
        return view('categories.show', compact('category')); // Vista para mostrar una categoría específica.
    }

    public function edit(Category $category)
    {
        $this->abortIfCategoryOutOfTenant($category);
        return view('categories.edit', compact('category')); // Vista para editar una categoría.
    }

    public function update(Request $request, Category $category)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenantId = $this->tenantWriteId($request);
        if ($tenantId <= 0 || (int) $category->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para editar esta categoría.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    }),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg,webp|max:5120',
        ]);

        // Manejar imagen
        if ($request->hasFile('image')) {

            // Eliminar imagen anterior
            if ($category->image && ImageStorage::exists($category->image)) {
                ImageStorage::delete($category->image);
            }

            // Guardar nueva imagen
            $category->image = ImageStorage::storeUploadedFile($request->file('image'), 'categories/images');
        }

        $category->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image'       => $category->image,
        ]);

        $category->refresh();

        return response()->json([
            'success' => true,
            'message'  => 'Categoría actualizada con éxito.',
            'category' => $category
        ], 200);
    }

    public function toggleStatus(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenantId = $this->tenantWriteId($request);
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo identificar el tenant.',
            ], 422);
        }

        $category = Category::where('tenant_id', $tenantId)->findOrFail($id);
        $reason = null;
        if ((bool) $category->is_active) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar la categoria.');
        }
    
        // Cambiar el estado de la categoría
        $category->is_active = !$category->is_active;
        $category->save();
    
        // Si la categoría se desactiva, desactivar también sus productos
        if ($category->is_active == 0) {
            Product::where('tenant_id', $tenantId)
                ->where('category_id', $category->id)
                ->update(['is_active' => 0]);

            ActionReason::log('categories', 'CATEGORY_DEACTIVATED', (string) $reason, [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'new_status' => $category->is_active
        ], 200);
    }
    

    public function destroy(Category $category)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $this->abortIfCategoryOutOfTenant($category);

        // Eliminar imagen si existe
        if ($category->image && ImageStorage::exists($category->image)) {
            ImageStorage::delete($category->image);
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría eliminada con éxito.');
    }

    private function resolveTenantId(Request $request): int
    {
        return $this->tenantScopeId($request);
    }

    private function abortIfCategoryOutOfTenant(Category $category): void
    {
        $tenantId = $this->tenantWriteId(request());
        if ($tenantId <= 0 || (int) $category->tenant_id !== $tenantId) {
            abort(403, 'No autorizado para operar esta categoría.');
        }
    }

}
