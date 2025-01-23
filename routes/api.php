<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
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
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/employee/dashboard', function () {
            return 'Employee Dashboard';
        });
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});