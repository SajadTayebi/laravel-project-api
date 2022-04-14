<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', [\App\Http\Controllers\Api\V1\UserController::class, 'register']);
Route::post('login', [\App\Http\Controllers\Api\V1\UserController::class, 'login']);
Route::middleware('auth:sanctum')->post('logout', [\App\Http\Controllers\Api\V1\UserController::class, 'logout']);

Route::apiResource('brands', \App\Http\Controllers\Api\V1\BrandController::class);
Route::get('brands/{brand}/product', [\App\Http\Controllers\Api\V1\BrandController::class, 'product']);

Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class);
Route::get('categories/{category}/children', [\App\Http\Controllers\Api\V1\CategoryController::class, 'children']);
Route::get('categories/{category}/parent', [\App\Http\Controllers\Api\V1\CategoryController::class, 'parent']);
Route::get('categories/{category}/products', [\App\Http\Controllers\Api\V1\CategoryController::class, 'product']);

Route::apiResource('products', \App\Http\Controllers\Api\V1\ProductController::class);

Route::post('payment/send', [\App\Http\Controllers\Api\V1\PaymentController::class, 'send']);
Route::post('payment/verify', [\App\Http\Controllers\Api\V1\PaymentController::class, 'verify']);


