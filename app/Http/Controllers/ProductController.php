<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Tax;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // private $client;

    // public function __construct()
    // {
    //     $this->client = new Client();
    //     // $this->client->setAuthConfig(storage_path('app/credentials.json'));
    //     $this->client->addScope(Drive::DRIVE_FILE);
    //     $this->client->setAccessType('offline');
    //     $this->client->setPrompt('select_account consent');
    // }
    public function index()
    {
        $user = auth()->user();

        $categories = Category::with(['products' => function ($query) use ($user) {
                $query->where('is_active', true)
                    ->where('tenant_id', $user->tenant_id)
                    ->with(['variants']);
            }])
            ->where('is_active', true)
            ->where('tenant_id', $user->tenant_id) // 👈 aquí filtras las categorías
            ->get();
        $taxes = Tax::all();

        $productItems = Product::with(['category', 'images', 'variants'])
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('products', compact('categories', 'productItems', 'taxes'));
    }

    public function indexCreateProduct()
    {
        $user = auth()->user();

        $categories = Category::where('tenant_id', $user->tenant_id)->get();
        $taxes = Tax::all();
        return view('createProductItem', compact('categories', 'taxes'));
    }

    public function getProducts()
    {
        $productItems = Product::with(['category', 'images', 'variants'])
        ->orderBy('created_at', 'desc')->get();
        return response()->json($productItems);
    }
    public function categoriesIndex()
    {
        $user = auth()->user();

        $categories = Category::where('tenant_id', $user->tenant_id)
            ->with(['products' => function ($query) use ($user) {
                $query->where('is_active', true)
                    ->where('tenant_id', $user->tenant_id)
                    ->with('variants');
            }])
            ->get();

        // Calcular total de stock por categoría
        foreach ($categories as $category) {
            $totalStock = 0;

            foreach ($category->products as $product) {
                foreach ($product->variants as $variant) {
                    $totalStock += $variant->stock;
                }
            }

            // Agregar el total como propiedad adicional para usarlo en la vista
            $category->total_available_items = $totalStock;
        }

        return view('categories', compact('categories'));
    }

    public function showByCategory($categoryId)
    {
        $user = auth()->user();

        $category = Category::findOrFail($categoryId);
        $categories = Category::where('tenant_id', $user->tenant_id)
        ->where('is_active', true)
        ->get();
        $productItems = Product::where('category_id', $category->id)
        ->orderBy('created_at', 'desc')
        ->get();
    
        return view('products', compact('productItems', 'category', 'categories'));
    }
    public function showByCategoryEcomm($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $categories = Category::all();
        $productItems = Product::where('category_id', $category->id)
        ->with(['images', 'variants'])
        ->orderBy('created_at', 'desc')
        ->get();
        return response()->json($productItems);
    }
    public function showByCategoryEcommAll()
    {
        try {
            // Obtener todos los productos con sus imágenes y variantes
            $productItems = Product::with(['images', 'variants'])->get();
    
            // Verificar si hay productos, de lo contrario devolver un mensaje adecuado
            if ($productItems->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }
    
            return response()->json($productItems);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function showByProduct($id)
    {
        $product = Product::with(['variants', 'images', 'category', 'taxes'])->findOrFail($id);
        $categories = Category::all();
        $taxes = Tax::all();
        return view('productItem', compact('product', 'categories', 'taxes'));
    }
    
    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required',
            'tenant_id' => 'required'
        ]);

        // Crear el producto
        Product::create($validatedData);
        return response()->json(['success' => true, 'message' => 'Product created successfully'], 200);

    }
    public function create(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            // Validaciones que necesites…
        ]);

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->productName,
            'description' => $request->productDescription,
            'tenant_id' => $request->tenant_id
        ]);

        // 📌 GUARDAR TAXES SELECCIONADOS (si existen)
        if ($request->has('tax_ids') && is_array($request->tax_ids)) {
            $product->taxes()->sync($request->tax_ids);
        }

        // Guardar imágenes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                ]);
            }
        }

        // Guardar Variantes
        $variants = $request->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }

        if (!empty($variants) && is_array($variants)) {
            foreach ($variants as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $variant['name'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Product created successfully']);
    }
    public function updateTaxes(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $product = Product::findOrFail($id);

        $request->validate([
            'taxes' => 'array',
            'taxes.*' => 'exists:taxes,id'
        ]);

        // Sincroniza las relaciones
        $product->taxes()->sync($request->taxes);

        return response()->json(['success' => true]);
    }

    // Función para agregar una imagen
    public function addImage(Request $request, $productId)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
            // Guardar la imagen en el almacenamiento
            $path = $request->file('image')->store('products', 'public');
    
            // Asociar la imagen al producto
            ProductImage::create([
                'product_id' => $productId,
                'path' => $path,
            ]);
    
            return redirect()->back()->with('success', 'Imagen agregada correctamente.');
        }
    
        // Función para eliminar una imagen
        public function removeImage($imageId)
        {
            $image = ProductImage::findOrFail($imageId);
    
            // Eliminar la imagen del almacenamiento
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
    
            // Eliminar el registro de la base de datos
            $image->delete();
    
            return response()->json(['success' => true, 'message' => 'Imagen eliminada correctamente.']);
        }
    
    public function storeGoogle(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filePath = $image->getPathName();
                $fileName = $image->getClientOriginalName();
                $fileMetadata = new Drive\DriveFile([
                    'name' => $fileName,
                    'parents' => ['your-folder-id']
                ]);

                $content = file_get_contents($filePath);
                $file = $service->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => $image->getMimeType(),
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);

                $fileId = $file->id;
                $fileUrl = "https://drive.google.com/uc?export=view&id={$fileId}";

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $fileUrl,
                ]);
            }
        }

        return response()->json(['message' => 'Product created successfully', 'product' => $product], 201);
    }

    public function show($id) {
        $product = Product::with(['images', 'variants', 'category'])->findOrFail($id);
        return response()->json($product);
    }
    
    public function update(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'category' => 'required|exists:categories,id',
            'is_active' => 'required|boolean',
        ]);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category_id' => $validated['category'],
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'product' => $product,
        ]);
    }

    public function generateReport(Request $request)
    {
        $products = Product::with('variants')
        ->where('tenant_id', $request->tenant_id)
        ->get();

        $csvData = "Nombre,Descripción,Precio,Stock Total\n";

        foreach ($products as $product) {
            $totalStock = $product->variants->sum('stock');
            $csvData .= "{$product->name},{$product->description},{$product->price},{$totalStock}\n";
        }

        $fileName = "reporte_productos_" . now()->format('Y-m-d') . ".csv";

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }
    
}
