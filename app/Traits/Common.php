<?php

namespace App\Traits;
use DB;

trait Common
{

    public function getProductDetails($productId)
    {
        try {
            $product = DB::table('products')
                ->leftJoin('categories', 'products.category', '=', 'categories.category_id')
                ->leftJoin('colors', 'products.color', '=', 'colors.color_id')
                ->leftJoin('frame_sizes', 'products.frame_sizes', '=', 'frame_sizes.frame_size_id')
                ->leftJoin('rim_types', 'products.rim_type', '=', 'rim_types.rim_type_id')
                ->leftJoin('styles', 'products.style', '=', 'styles.style_id')
                ->leftJoin('materials', 'products.material', '=', 'materials.material_id')
                ->leftJoin('shapes', 'products.shape', '=', 'shapes.shape_id')
                ->leftJoin('manufacturers', 'products.manufacturer_name', '=', 'manufacturers.manufacturer_id')
                ->where('products.product_id', $productId)
                ->select(
                    'products.*',
                    'categories.category_name as category',
                    'colors.color_name as color',
                    'frame_sizes.frame_size_name as frame_sizes',
                    'rim_types.rim_type_name as rim_type',
                    'styles.style_name as style',
                    'materials.material_name as material',
                    'shapes.shape_name as shape',
                    'manufacturers.manufacturer_name as manufacturer_name'
                )
                ->first();

            return $product;
        } catch (\Exception $e) {
           dd($e->getMessage());
        }
    }

    public function getMappedProducts($type)
    {
        try {
            $mapperTable = $type === 'company' ? 'company_product_mapper' : 'employee_product_mapper';
            $foreignKey = $type === 'company' ? 'company_id' : 'employee_id';
            $authKey = $type === 'company' ? auth('sanctum')->user()->company_id : auth('sanctum')->user()->employee_id;

            $products = DB::table('products')
                ->join($mapperTable, 'products.product_id', '=', "$mapperTable.product_id")
                ->leftJoin('categories', 'products.category', '=', 'categories.category_id')
                ->leftJoin('colors', 'products.color', '=', 'colors.color_id')
                ->leftJoin('frame_sizes', 'products.frame_sizes', '=', 'frame_sizes.frame_size_id')
                ->leftJoin('rim_types', 'products.rim_type', '=', 'rim_types.rim_type_id')
                ->leftJoin('styles', 'products.style', '=', 'styles.style_id')
                ->leftJoin('materials', 'products.material', '=', 'materials.material_id')
                ->leftJoin('shapes', 'products.shape', '=', 'shapes.shape_id')
                ->leftJoin('manufacturers', 'products.manufacturer_name', '=', 'manufacturers.manufacturer_id')
                ->where("$mapperTable.$foreignKey", $authKey)
                ->select(
                    'products.*',
                    'categories.category_name as category',
                    'colors.color_name as color',
                    'frame_sizes.frame_size_name as frame_sizes',
                    'rim_types.rim_type_name as rim_type',
                    'styles.style_name as style',
                    'materials.material_name as material',
                    'shapes.shape_name as shape',
                    'manufacturers.manufacturer_name as manufacturer_name'
                )
                ->get();

                return $products;

        } catch (\Exception $e) {
            dd($e->getMessage());

        }
    }


}
