<?php

namespace App\Traits;
use DB;
use Illuminate\Http\Request;

trait common
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

    public function getOrders(Request $request, $type)
{

    $query = "SELECT
                o.*,  -- All columns from orders
                JSON_PRETTY(
                    JSON_OBJECT(
                        'employee', employees.employee_data,
                        'company', companies.company_data
                    )
                ) AS `data`
            FROM
                orders o
            LEFT JOIN (
                SELECT
                    e.id,
                    JSON_OBJECT(
                        'employee_id', e.id,
                        'employee_name', (
                            SELECT name
                            FROM users
                            WHERE users.employee_id = e.id
                            LIMIT 1
                        ),
                        'email', (
                            SELECT email
                            FROM users
                            WHERE users.employee_id = e.id
                            LIMIT 1
                        )
                    ) AS `employee_data`
                FROM
                    employees e
                GROUP BY
                    e.id
            ) AS employees ON o.employee_id = employees.id

            LEFT JOIN (
                SELECT
                    c.id,
                    JSON_OBJECT(
                        'company_id', c.id,
                        'company_name', (
                            SELECT name
                            FROM users
                            WHERE users.company_id = c.id
                            LIMIT 1
                        ),
                        'email', (
                            SELECT email
                            FROM users
                            WHERE users.company_id = c.id
                            LIMIT 1
                        )
                    ) AS `company_data`
                FROM
                    companies c
                GROUP BY
                    c.id
            ) AS companies ON o.company_id = companies.id
            
            ";



    $conditions = [];
    $parameters = [];

    if ($request->has('order_type')) {
        $conditions[] = "o.order_type = ?";
        $parameters[] = $request->order_type;
    }

    if ($request->has('order_status')) {
        $conditions[] = "o.order_status = ?";
        $parameters[] = $request->order_status;
    }

    if ($type == 'employee') {
        $conditions[] = "o.employee_id = ?";
        $parameters[] = auth('sanctum')->user()->employee_id;
    }

    if ($type == 'company') {
        $conditions[] = "o.company_id = ?";
        $parameters[] = auth('sanctum')->user()->company_id;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query = $query . ' ORDER BY o.id';
    $orders = DB::select($query, $parameters);
    
    foreach ($orders as $order) {
    // Ensure $order->data is a decoded object or array
    if (is_string($order->data)) {
        $order->data = json_decode($order->data);
    }

    // Check if $order->data is now an object before accessing properties
    if (is_object($order->data) && isset($order->data->employee)) {
        $order->employee_data = is_string($order->data->employee) ? json_decode($order->data->employee) : $order->data->employee;
    }

    if (is_object($order->data) && isset($order->data->company)) {
        $order->company_data = is_string($order->data->company) ? json_decode($order->data->company) : $order->data->company;
    }

    unset($order->data);
}

return $orders;

}

    


}
