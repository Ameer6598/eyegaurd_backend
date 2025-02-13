<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Artisan::call('config:clear');
Artisan::call('cache:clear');
Artisan::call('view:clear');
Artisan::call('route:clear');
