<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPassword;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\BannerMktController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductSizeController;
use App\Http\Controllers\API\ProductColorController;
use App\Http\Controllers\API\ProductImageController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\FavouriteListController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\OrderItemController;
use App\Http\Controllers\API\ProductVariantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes for authentication
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('refresh', [LoginController::class, 'refresh']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile/update/{id}', [UserController::class, 'update']);
    Route::post('password/forgot', [ResetPassword::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPassword::class, 'reset'])->name('password.reset');
});

// Routes for admin
Route::group(['middleware' => ['api', 'role:admin'], 'prefix' => 'admin'], function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('banners', BannerMktController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product/colors', ProductColorController::class);
    Route::apiResource('product/images', ProductImageController::class);
    Route::apiResource('product/sizes', ProductSizeController::class);
    Route::apiResource('product/variants', ProductVariantController::class);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
});

// Routes for staff
Route::group(['middleware' => ['api', 'role:staff'], 'prefix' => 'staff'], function () {
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('order/items', OrderItemController::class);
    Route::apiResource('favourites', FavouriteListController::class);
    Route::apiResource('comments', CommentController::class);
    Route::apiResource('carts', CartController::class);
});

// Routes for customers
Route::group(['middleware' => ['api', 'role:customer'], 'prefix' => 'customer'], function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('carts', [CartController::class, 'store']);
    Route::get('carts', [CartController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
});
