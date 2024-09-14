<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group(
    [
        'middleware' => 'api',
        'prefix' => 'auth',
    ],
    function ($router) {
        Route::post('login', [LoginController::class, 'login']);
        Route::post('logout', [LoginController::class, 'logout']);
        Route::post('refresh', [LoginController::class, 'refresh']);
        Route::get('profile', [LoginController::class, 'profile']);
        Route::post('register', [RegisterController::class, 'register']);
        Route::post('destroy', [RegisterController::class, 'register']);
    },

);
Route::group(
    [
        'middleware' => 'api',
        'prefix' => 'admin',
    ],
    function ($router) {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('brands', BrandController::class);
    },
);
