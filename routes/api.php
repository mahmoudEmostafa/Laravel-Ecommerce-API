<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix("auth")->group(function () {
     Route::post("/register",[AuthController::class,"register"]);
     Route::post("/login",[AuthController::class,"login"])->middleware('throttle:5,1');;
     Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/profile', [AuthController::class, 'profile']);

    });

     } );

Route::post('/verify-otp', [AuthController::class,'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])
    ->middleware('throttle:3,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//test role 
Route::get('/test-role', function (Request $request) {
    return $request->user()->hasRole('admin');
})->middleware('auth:sanctum');





//product api

Route::post('/products', [ProductController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/products/{id}', [ProductController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::delete('/products/{id}', [ProductController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::post('/products/{product}/image', [ProductController::class, 'uploadImage'])
    ->middleware(['auth:sanctum', 'role:admin']);

//Cart api 

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/cart/add', [CartController::class, 'add']);

    Route::get('/cart', [CartController::class, 'index']);

    Route::put('/cart/item/{id}', [CartController::class, 'update']);

    Route::delete('/cart/item/{id}', [CartController::class, 'remove']);

});



//Order api

Route::post('/checkout', [OrderController::class, 'checkout'])
    ->middleware('auth:sanctum');
Route::get('/my-orders', [OrderController::class, 'myOrders'])
    ->middleware('auth:sanctum');
Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->middleware('auth:sanctum');

//admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('/admin/orders', [OrderController::class, 'adminOrders']);

    Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);

    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);

});


//payment
Route::post('/checkout/{orderId}', [PaymentController::class, 'checkout'])
    ->middleware('auth:sanctum');