<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Support\ImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $categories = Category::with(['products' => function ($query) use ($user) {
            $query->where('is_active', true)->where('tenant_id', $user->tenant_id)->with(['variants']);
        }])->get();
    
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
        $tenantId = $request->tenant_id; 

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

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('tenant_id', $request->input('tenant_id'));
                }),
            ],
            'description' => 'nullable|string',
            'tenant_id' => 'required',
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
                'tenant_id' => $validated['tenant_id'],
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
                'tenant_id' => $validated['tenant_id'] ?? null,
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
        return view('categories.show', compact('category')); // Vista para mostrar una categoría específica.
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category')); // Vista para editar una categoría.
    }

    public function update(Request $request, Category $category)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($category) {
                        return $query->where('tenant_id', $category->tenant_id);
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

    public function toggleStatus($id)
    {
        DB::raw("SET @user_id = " . auth()->id());
        // Buscar la categoría
        $category = Category::findOrFail($id);
    
        // Cambiar el estado de la categoría
        $category->is_active = !$category->is_active;
        $category->save();
    
        // Si la categoría se desactiva, desactivar también sus productos
        if ($category->is_active == 0) {
            Product::where('category_id', $category->id)->update(['is_active' => 0]);
        }
    
        return response()->json([
            'status' => 'success',
            'new_status' => $category->is_active
        ], 200);
    }
    

    public function destroy(Category $category)
    {
        DB::raw("SET @user_id = " . auth()->id());

        // Eliminar imagen si existe
        if ($category->image && ImageStorage::exists($category->image)) {
            ImageStorage::delete($category->image);
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría eliminada con éxito.');
    }

}
