<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPassword;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\TagController;
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
        Route::post('register', [RegisterController::class, 'register']);
        Route::post('logout', [LoginController::class, 'logout']);
        Route::post('refresh', [LoginController::class, 'refresh']);
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile/update/{id}', [UserController::class, 'update']);
//        Route::post('destroy', [UserController::class, 'destroy']);

        Route::post('password/forgot', [ResetPassword::class, 'sendResetLinkEmail']);

        // Route để xử lý khi người dùng nhấn vào link reset mật khẩu
        Route::post('password/reset', [ResetPassword::class, 'reset'])->name('password.reset');
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
        Route::apiResource('tags', TagController::class);
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show']);
    },
);
