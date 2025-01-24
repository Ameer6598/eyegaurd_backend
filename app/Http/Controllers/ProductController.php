<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\Common;
use Illuminate\Http\Request;
use DB;
use App\Models\Category;
use App\Models\Color;
use App\Models\CompanyProduct;
use App\Models\FrameSize;
use App\Models\RimType;
use App\Models\Style;
use App\Models\Material;
use App\Models\Shape;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{   
    use ApiResponse;
    use Common;
    


    public function create(Request $request)
    {
        try {
            $request->validate([
                'product_name' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|numeric',
                'color' => 'required|numeric',
                'frame_sizes' => 'required|numeric',
                'gender' => 'required',
                'rim_type' => 'required|numeric',
                'material' => 'required|numeric',
                'price' => 'required|numeric|min:0',
                'available_quantity' => 'required|integer|min:0',
                'status' => 'required|numeric',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
            ]);

            DB::beginTransaction();
            $images = $request->hasFile('images') ? $this->uploadImages($request->file('images')) : null;

            $product = Product::create(array_merge(
                $request->except('images'),
                ['images' => $images]
            ));
            $companies = DB::table('companies')->select('id')->get();
            $mappings = $companies->map(function ($company) use ($product) {
                return [
                    'product_id' => $product->product_id,
                    'company_id' => $company->id,
                    'created_at' => now(),
                ];
            })->toArray();

            CompanyProduct::insert($mappings);

            DB::commit();
            return $this->successResponse(['model' => 'products'], 'Product created successfully', [
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'products'], $e->getMessage(), [], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'product_name' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|numeric',
                'color' => 'required|numeric',
                'frame_sizes' => 'required|numeric',
                'gender' => 'required',
                'rim_type' => 'required|numeric',
                'material' => 'required|numeric',
                'price' => 'required|numeric|min:0',
                'available_quantity' => 'required|integer|min:0',
                'status' => 'required|numeric',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'product_id' => 'required'
            ]);

            DB::beginTransaction();
            $product = Product::findOrFail($request->product_id);

            $newImages = $request->hasFile('images') ? $this->uploadImages($request->file('images')) : null;
            // $mergedImages = $newImages ? ($product->images ? $product->images . ',' . $newImages : $newImages) : $product->images;

            $product->update(array_merge(
                $request->except('images'),
                ['images' => $newImages]
            ));

            DB::commit();

            return $this->successResponse(['model' => 'products'], 'Product updated successfully', [
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'products'], $e->getMessage(), [], 500);

        }
    }

    public function getProductOrAll($productId = null)
    {
        try {
            $baseUrl = config('app.url'); 

            if ($productId) {
                $product = DB::table('products')
                    ->where('product_id', $productId)
                    ->where('product_status', 1)
                    ->first();

                if (!$product) {
                    return $this->errorResponse(['model' => 'products'], 'Product not found', [], 404);
                }

                $product->images = $this->processImages($product->images, $baseUrl);

                return $this->successResponse(['model' => 'products'], 'Product retrieved successfully', [
                    'product' => $product,
                ]);
            } else {
                $products = DB::table('products')->where('product_status', 1)->get();

                foreach ($products as $product) {
                    $product->images = $this->processImages($product->images, $baseUrl);
                }
                return $this->successResponse(['model' => 'products'], 'All products retrieved successfully', [
                    'products' => $products,
                ]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'products'], $e->getMessage(), [], 500);
        }
    }
    public function getFullProductDetail($productId)
    {
        try {
            $baseUrl = config('app.url'); 

            $product = $this->getProductDetails($productId);
            if (!$product) {
                return $this->errorResponse(['model' => 'products'], 'Product not found', [], 404);
            }

            $product->images = $this->processImages($product->images, $baseUrl);

            return $this->successResponse(['model' => 'products'], 'Product retrieved successfully', [
                'product' => $product,
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'products'], $e->getMessage(), [], 500);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($productId);
            $product->product_status = 0;
            $product->save();


            DB::commit();
            return $this->successResponse(['model' => 'products'], 'Product deleted successfully',[]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'products'], $e->getMessage(), [], 500);

        }
    }

    public function getCategories()
    {
        return $this->successResponse(['model' => 'category'], 'Categories fetched successfully', [
            'categories' => Category::all(),
        ]);
    }

    public function createCategory(Request $request)
    {
        $data = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction(); 

            $category = Category::create($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'category'], 'Category created successfully', [
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'category'], $e->getMessage(), [], 500);
        }
    }

    


    public function updateCategory(Request $request)
    {
        $data = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'category_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $category = Category::findOrFail($data['category_id']); 

            $category->update($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'category'], 'Category updated successfully', [
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return $this->errorResponse(['model' => 'category'], $e->getMessage(), [], 500);
        }
    }

    public function deleteCategory($id)
    {
        DB::beginTransaction(); 

        try {
            $category = Category::findOrFail($id); 
            $category->delete(); 

            DB::commit(); 

            return $this->successResponse(['model' => 'category'], 'Category deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return $this->errorResponse(['model' => 'category'], $e->getMessage(), [], 500);
        }
    }

    public function getColors()
    {
        return $this->successResponse(['model' => 'color'], 'Colors fetched successfully', [
            'colors' => Color::all(),
        ]);
    }

    public function createColor(Request $request)
    {
        $data = $request->validate([
            'color_name' => 'required|string|max:255',
            'frame_sizes' => 'nullable|string',
        ]);

        DB::beginTransaction(); 

        try {
            $color = Color::create($data);

            DB::commit(); 
            return $this->successResponse(['model' => 'color'], 'Color created successfully', [
                'color' => $color,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'color'], $e->getMessage(), [], 500);
        }
    }

    public function updateColor(Request $request)
    {

        $data = $request->validate([
            'color_name' => 'required|string|max:255',
            'frame_sizes' => 'nullable|string',
            'color_id' => 'required'
        ]);

        DB::beginTransaction(); 

        try {
            $color = Color::findOrFail($request->color_id); 

            $color->update($data);

            DB::commit(); 
            return $this->successResponse(['model' => 'color'], 'Color updated successfully', [
                'color' => $color,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return $this->errorResponse(['model' => 'color'], $e->getMessage(), [], 500);
        }
    }

    public function deleteColor($id)
    {
        DB::beginTransaction(); 

        try {
            $color = Color::findOrFail($id); 
            $color->delete();

            DB::commit(); 

            return $this->successResponse(['model' => 'color'], 'Color deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'color'], $e->getMessage(), [], 500);
        }
    }

    public function getFrameSizes()
    {
        return $this->successResponse(['model' => 'frame_size'], 'Frame sizes fetched successfully', [
            'frame_sizes' => FrameSize::all(),
        ]);
    }

    public function createFrameSize(Request $request)
    {
        $data = $request->validate([
            'frame_size_name' => 'required|string|max:255',
            'rim_type' => 'nullable|string',
        ]);

        DB::beginTransaction(); 

        try {
            $frameSize = FrameSize::create($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'frame_size'], 'Frame Size created successfully', [
                'frame_size' => $frameSize,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'frame_size'], $e->getMessage(), [], 500);
        }
    }

    public function updateFrameSize(Request $request)
    {

        $data = $request->validate([
            'frame_size_name' => 'required|string|max:255',
            'rim_type' => 'nullable|string',
            'frame_size_id' => 'required'
        ]);

        DB::beginTransaction(); 

        try {
            $frameSize = FrameSize::findOrFail($data['frame_size_id']); 

            $frameSize->update($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'frame_size'], 'Frame Size updated successfully', [
                'frame_size' => $frameSize,
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'frame_size'], $e->getMessage(), [], 500);
        }
    }

    public function deleteFrameSize($id)
    {
        DB::beginTransaction(); 

        try {
            $frameSize = FrameSize::findOrFail($id); 
            $frameSize->delete(); 

            DB::commit(); 

            return $this->successResponse(['model' => 'frame_size'], 'Frame Size deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'frame_size'], $e->getMessage(), [], 500);
        }
    }


    public function getRimTypes()
    {
        return $this->successResponse(['model' => 'frame_size'], 'Rim Types fetched successfully', [
            'rim_types' => RimType::all(),
        ]);
    }

    public function createRimType(Request $request)
    {
        DB::beginTransaction(); 

        try {
            $data = $request->validate([
                'rim_type_name' => 'required|string|max:255',
                'style' => 'nullable|string', 
            ]);

            $rimType = RimType::create($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'rim_type'], 'Rim Type created successfully', $rimType); 
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'rim_type'], $e->getMessage(), [], 500);
        }
    }

    public function updateRimType(Request $request)
    {
        DB::beginTransaction(); 

        try {

            $data = $request->validate([
                'rim_type_name' => 'required|string|max:255',
                'style' => 'nullable|string',
                'rim_type_id' => 'required'
            ]);
            $rimType = RimType::findOrFail($data['rim_type_id']);

            $rimType->update($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'rim_type'], 'Rim Type updated successfully', $rimType);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'rim_type'], $e->getMessage(), [], 500);
        }
    }

    public function deleteRimType($id)
    {
        DB::beginTransaction(); 

        try {
            $rimType = RimType::findOrFail($id);
            $rimType->delete();

            DB::commit(); 

            return $this->successResponse(['model' => 'rim_type'], 'Rim Type deleted successfully', []); 
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'rim_type'], $e->getMessage(), [], 500);
        }
    }

    public function getStyles()
    {
        return $this->successResponse(['model' => 'frame_size'], 'Style fetched successfully', [
            'rim_types' => Style::all(),
        ]);
    }

    public function createStyle(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'style_name' => 'required|string|max:255',
                'material' => 'nullable|string|max:255', 
            ]);

            $style = Style::create($data);

            DB::commit();

            return $this->successResponse(['model' => 'style'], 'Style created successfully', $style);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'style'], $e->getMessage(), [], 500);
        }
    }
    
    public function updateStyle(Request $request)
    {
        DB::beginTransaction(); 

        try {

            $data = $request->validate([
                'style_name' => 'required|string|max:255',
                'material' => 'nullable|string|max:255', 
                'style_id' => 'required'
            ]);
            $style = Style::findOrFail($data['style_id']);

            $style->update($data);

            DB::commit(); 

            return $this->successResponse(['model' => 'style'], 'Style updated successfully', $style);
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'style'], $e->getMessage(), [], 500);
        }
    }

    public function deleteStyle($id)
    {
        DB::beginTransaction(); 

        try {
            $style = Style::findOrFail($id);
            $style->delete();

            DB::commit(); 

            return $this->successResponse(['model' => 'style'], 'Style deleted successfully', []); 
        } catch (\Exception $e) {
            DB::rollBack(); 

            return $this->errorResponse(['model' => 'style'], $e->getMessage(), [], 500);
        }
    }

    public function createMaterial(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'material_name' => 'required|string|max:255',
                'shape' => 'nullable|string|max:255', 
            ]);

            $material = Material::create($data);

            DB::commit();

            return $this->successResponse(['model' => 'material'], 'Material created successfully', $material);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'material'], $e->getMessage(), [], 500);
        }
    }

    public function getMaterials()
    {
        try {
            $materials = Material::all();
            return $this->successResponse(['model' => 'material'], 'Materials retrieved successfully', $materials);
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'material'], $e->getMessage(), [], 500);
        }
    }

    public function updateMaterial(Request $request) 
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([
                'material_name' => 'required|string|max:255',
                'shape' => 'nullable|string|max:255', 
                'material_id' => 'required'
            ]);

            $material = Material::findOrFail($data['material_id']); 

            $material->update($data);

            DB::commit();

            return $this->successResponse(['model' => 'material'], 'Material updated successfully', $material);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'material'], $e->getMessage(), [], 500);
        }
    }

    public function deleteMaterial($id) 
    {
        DB::beginTransaction();

        try {
            $material = Material::findOrFail($id);
            $material->delete();

            DB::commit();

            return $this->successResponse(['model' => 'material'], 'Material deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'material'], $e->getMessage(), [], 500);
        }
    }

    public function createShape(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'shape_name' => 'required|string|max:255',
                'manufacturer_name' => 'nullable|string|max:255',
            ]);

            $shape = Shape::create($data);

            DB::commit();

            return $this->successResponse(['model' => 'shape'], 'Shape created successfully', $shape);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'shape'], $e->getMessage(), [], 500);
        }
    }

    public function getShapes()
    {
        try {
            $shapes = Shape::all();
            return $this->successResponse(['model' => 'shape'], 'Shapes retrieved successfully', $shapes);
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'shape'], $e->getMessage(), [], 500);
        }
    }

    public function updateShape(Request $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([
                'shape_name' => 'required|string|max:255',
                'manufacturer_name' => 'nullable|string|max:255',
                'shape_id' => 'required'
            ]);
            $shape = Shape::findOrFail($data['shape_id']);

            $shape->update($data);

            DB::commit();

            return $this->successResponse(['model' => 'shape'], 'Shape updated successfully', $shape);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'shape'], $e->getMessage(), [], 500);
        }
    }

    public function deleteShape($id)
    {
        DB::beginTransaction();

        try {
            $shape = Shape::findOrFail($id);
            $shape->delete();

            DB::commit();

            return $this->successResponse(['model' => 'shape'], 'Shape deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'shape'], $e->getMessage(), [], 500);
        }
    }

    public function createManufacturer(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'manufacturer_name' => 'required|string|max:255',
                'contact_info' => 'nullable|string', 
            ]);

            $manufacturer = Manufacturer::create($data);

            DB::commit();

            return $this->successResponse(['model' => 'manufacturer'], 'Manufacturer created successfully', $manufacturer);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'manufacturer'], $e->getMessage(), [], 500);
        }
    }

    public function getManufacturers()
    {
        try {
            $manufacturers = Manufacturer::all();
            return $this->successResponse(['model' => 'manufacturer'], 'Manufacturers retrieved successfully', $manufacturers);
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'manufacturer'], $e->getMessage(), [], 500);
        }
    }

    public function updateManufacturer(Request $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([
                'manufacturer_name' => 'required|string|max:255',
                'contact_info' => 'nullable|string', 
                'manufacturer_id' => 'required'
            ]);
            $manufacturer = Manufacturer::findOrFail($data['manufacturer_id']);

            $manufacturer->update($data);

            DB::commit();

            return $this->successResponse(['model' => 'manufacturer'], 'Manufacturer updated successfully', $manufacturer);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'manufacturer'], $e->getMessage(), [], 500);
        }
    }

    public function deleteManufacturer($id)
    {
        DB::beginTransaction();

        try {
            $manufacturer = Manufacturer::findOrFail($id);
            $manufacturer->delete();

            DB::commit();

            return $this->successResponse(['model' => 'manufacturer'], 'Manufacturer deleted successfully', []);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(['model' => 'manufacturer'], $e->getMessage(), [], 500);
        }
    }


    private function uploadImages($images, $directory = 'products')
    {
        $uploadedImagePaths = [];

        foreach ($images as $image) {
            $path = $image->store($directory, 'public');
            $uploadedImagePaths[] = Storage::url($path); 
        }

        return implode(',', $uploadedImagePaths);
    }

    private function processImages($images, $baseUrl)
    {
        if (!$images) {
            return [];
        }
        $imagePaths = explode(',', $images); 
        return array_map(fn($image) => $baseUrl . $image, $imagePaths); 
    }

}
