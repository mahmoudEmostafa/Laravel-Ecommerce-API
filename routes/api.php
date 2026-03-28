<?php

use App\Http\Controllers\Auth\AuthController;
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
Route::get('/test-role', function () {
    return auth()->user()->hasRole('admin');
})->middleware('auth:sanctum');