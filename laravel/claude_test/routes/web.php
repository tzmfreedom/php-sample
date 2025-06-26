<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypedTemplateController;
use App\Http\Controllers\TypedBladeController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/typed-template-example', [TypedTemplateController::class, 'example']);
Route::get('/typed-template-error', [TypedTemplateController::class, 'typeMismatchExample']);

Route::get('/typed-blade/user-profile', [TypedBladeController::class, 'userProfile']);
Route::get('/typed-blade/products', [TypedBladeController::class, 'productList']);
Route::get('/typed-blade/error', [TypedBladeController::class, 'typeMismatchExample']);
