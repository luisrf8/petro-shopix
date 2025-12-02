<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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

        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'tenant_id' => 'required'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'tenant_id' => $request->tenant_id
        ]);

        // return redirect()->route('categories.index')->with('success', 'Categoría creada con éxito.');
        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);

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
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
    
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);
    
        // Recargar la categoría con los datos actualizados
        $category->refresh();
        return response()->json([
            'message' => 'Categoría actualizada con éxito.',
            'category' => $category
        ], 200); // Cambié el código de estado a 200 para indicar éxito en una actualización
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

        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Categoría eliminada con éxito.');
    }
}
