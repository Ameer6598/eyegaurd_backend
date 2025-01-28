<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Hash;


Route::get('/usersss', function () {
    
    return 'API User route done'. Hash::make('admin');
});
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('role:owner')->group(function () {
        Route::prefix('company/')->group(function(){
            Route::get('companies', [CompanyController::class, 'getAll']);
            Route::post('create',[CompanyController::class,'create']);
            Route::post('update',[CompanyController::class,'update']);
            Route::delete('{companyId}', [CompanyController::class, 'delete']);
            Route::get('{companyId}', [CompanyController::class, 'getCompany']);
        });

        // Categories
        Route::post('/categories', [ProductController::class, 'createCategory']); 
        Route::get('/categories', [ProductController::class, 'getCategories']);
        Route::post('/categorie-update', [ProductController::class, 'updateCategory']);
        Route::delete('/categories/{id}', [ProductController::class, 'deleteCategory']);

        // Colors
        Route::post('/colors', [ProductController::class, 'createColor']);
        Route::get('/colors', [ProductController::class, 'getColors']);
        Route::post('/color-update', [ProductController::class, 'updateColor']);
        Route::delete('/colors/{id}', [ProductController::class, 'deleteColor']);

        // Frame Sizes
        Route::post('/framesizes', [ProductController::class, 'createFrameSize']);
        Route::get('/framesizes', [ProductController::class, 'getFrameSizes']);
        Route::post('/framesize-update', [ProductController::class, 'updateFrameSize']);
        Route::delete('/framesizes/{id}', [ProductController::class, 'deleteFrameSize']);

        // Rim Types
        Route::post('/rimtypes', [ProductController::class, 'createRimType']);
        Route::get('/rimtypes', [ProductController::class, 'getRimTypes']);
        Route::post('/rimtype-update', [ProductController::class, 'updateRimType']);
        Route::delete('/rimtypes/{id}', [ProductController::class, 'deleteRimType']);

        // Styles
        Route::post('/styles', [ProductController::class, 'createStyle']);
        Route::get('/styles', [ProductController::class, 'getStyles']);
        Route::post('/style-update', [ProductController::class, 'updateStyle']);
        Route::delete('/styles/{id}', [ProductController::class, 'deleteStyle']);

        // Materials
        Route::post('/materials', [ProductController::class, 'createMaterial']);
        Route::get('/materials', [ProductController::class, 'getMaterials']);
        Route::post('/material-update', [ProductController::class, 'updateMaterial']);
        Route::delete('/materials/{id}', [ProductController::class, 'deleteMaterial']);

        // Shapes
        Route::post('/shapes', [ProductController::class, 'createShape']);
        Route::get('/shapes', [ProductController::class, 'getShapes']);
        Route::post('/shape-update', [ProductController::class, 'updateShape']);
        Route::delete('/shapes/{id}', [ProductController::class, 'deleteShape']);

        // Manufacturers
        Route::post('/manufacturers', [ProductController::class, 'createManufacturer']);
        Route::get('/manufacturers', [ProductController::class, 'getManufacturers']);
        Route::post('/manufacturer-update', [ProductController::class, 'updateManufacturer']);
        Route::delete('/manufacturers/{id}', [ProductController::class, 'deleteManufacturer']);

        Route::prefix('product')->group(function () {
            Route::post('create', [ProductController::class, 'create']);
            Route::get('all', [ProductController::class, 'getProductOrAll']);
            Route::get('{productId}', [ProductController::class, 'getProductOrAll']);
            Route::get('/details/{productId}', [ProductController::class, 'getFullProductDetail']);
            Route::post('update', [ProductController::class, 'update']);
            Route::delete('delete/{productId}', [ProductController::class, 'deleteProduct']);
        });

        Route::get('get-all-orders', [OrderController::class, 'getAllOrders']);
        Route::post('update-order-status', [OrderController::class, 'updateOrderStatus']);


    });

    Route::middleware('role:company')->group(function () {
        Route::prefix('employee/')->group(function(){
            
            Route::post('import', [EmployeeController::class, 'import']);
            Route::get('export', [EmployeeController::class, 'downloadSample']);
            
            Route::get('employees', [EmployeeController::class, 'getAll']);
            Route::post('create',[EmployeeController::class,'create']);
            Route::post('update',[EmployeeController::class,'update']);
            Route::post('update-benefit-amount',[EmployeeController::class,'bulkUpdate']);
            Route::delete('{employeeId}', [EmployeeController::class, 'delete']);
            Route::get('{employeeId}', [EmployeeController::class, 'getEmployee']);
        });
        Route::get('get-company-products', [ProductController::class, 'getCompanyProducts']);
        Route::get('get-company-orders', [OrderController::class, 'getCompanyOrders']);

    });

    Route::middleware('role:employee')->group(function () {

        Route::get('get-employee-products', [ProductController::class, 'getemployeeProducts']);
        Route::post('create-order', [OrderController::class, 'storeOrder']);
        Route::get('get-employee-orders', [OrderController::class, 'getEmployeeOrders']);


    });

    Route::post('/logout', [AuthController::class, 'logout']);
});