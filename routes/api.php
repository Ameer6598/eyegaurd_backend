<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
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

        Route::get('/owner/dashboard', function () {
            return 'Owner Dashboard';
        });
    });

    Route::middleware('role:company')->group(function () {
        Route::get('/company/dashboard', function () {
            return 'Company Dashboard';
        });
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/employee/dashboard', function () {
            return 'Employee Dashboard';
        });
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});