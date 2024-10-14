<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPassword;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\BannerMktController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductColorController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProductImageController;
use App\Http\Controllers\API\ProductSizeController;
use App\Http\Controllers\API\ProductVariantController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\Api\UserCommentController;
use App\Http\Controllers\API\VouCherController;
use Illuminate\Support\Facades\Route;

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

//AUTH
Route::group(
    [
        'middleware' => 'api',
        'prefix' => 'auth',
    ],
    function ($router) {
        Route::post('login', [LoginController::class, 'login']);
        Route::post('register', [RegisterController::class, 'register']);

        // Cần middleware auth:api để chỉ người dùng đăng nhập mới có thể đăng xuất
        Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:api');

        // Làm mới token, cần kiểm tra đã đăng nhập
        Route::post('refresh', [LoginController::class, 'refresh'])->middleware('auth:api');

        Route::get('profile', [UserController::class, 'profile'])->middleware('auth:api');
        Route::put('profile/update/{id}', [UserController::class, 'update'])->middleware('auth:api');

        Route::post('password/forgot', [ResetPassword::class, 'sendResetLinkEmail']);
        Route::post('password/reset', [ResetPassword::class, 'reset'])->name('password.reset');

        Route::get('users/{id}', [UserController::class, 'show']);


    }
);


//NHỮNG ROUTER CẦN CHECK ĐĂNG NHẬP
Route::group(
    [
        'middleware' => ['api','auth:api'],
    ],
    function ($router) {
        Route::apiResource('addresses', AddressController::class);
        Route::put('/addresses/{id}/default', [AddressController::class, 'setDefault'])->name('addresses.setDefault');
//        Route::get('voucher', [VouCherController::class, 'index']);
//        Route::post('voucher', [VouCherController::class, 'store']);
//        Route::put('voucher/{id}', [VouCherController::class, 'update']);
//        Route::get('voucher/{id}', [VouCherController::class, 'show']);
//        Route::delete('voucher/{id}', [VouCherController::class, 'destroy']);
    }
);


//Những đầu route không cần check đăng nhập và role vất vào đây
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{id}', [ProductController::class,'show'])->name('products.show');

//ADMIN
Route::group(
    [
        'middleware' => ['auth:api', 'role:admin', 'admin'],
        'prefix' => 'admin',
    ],
    function ($router) {

        //CATEGORIES
        Route::get('categories/trashed', [CategoryController::class, 'trashed']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);


        Route::apiResource('voucher', VouCherController::class);
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('tags', TagController::class);

        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::apiResource('product/colors', ProductColorController::class);
        Route::apiResource('product/images', ProductImageController::class);
        Route::apiResource('product/sizes', ProductSizeController::class);
        Route::apiResource('product/variants', ProductVariantController::class);

        Route::get('users', [UserController::class, 'index']);

         // Comments routes
        Route::get('comments', [CommentController::class, 'index']);
        Route::post('comments', [CommentController::class, 'store']);
        Route::get('comments/{id}', [CommentController::class, 'show']);
        Route::put('comments/{id}', [CommentController::class, 'update']);
        Route::delete('comments/{id}', [CommentController::class, 'destroy']);

        Route::get('banners', [BannerMktController::class, 'index']);
        Route::post('banners', [BannerMktController::class, 'store']);
        Route::put('banners/{id}', [BannerMktController::class, 'update']);
        Route::get('banners/{id}', [BannerMktController::class, 'show']);
        Route::delete('banners/{id}', [BannerMktController::class, 'destroy']);


    }
);

Route::get('voucher', [VouCherController::class, 'index']);
Route::post('voucher', [VouCherController::class, 'store']);
Route::put('voucher/{id}', [VouCherController::class, 'update']);
Route::get('voucher/{id}', [VouCherController::class, 'show']);
Route::delete('voucher/{id}', [VouCherController::class, 'destroy']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);


//STAFF
//Route::group(
//    [
//        'middleware' => ['auth:api', 'role:staff'],
//        'prefix' => 'staff',
//    ],
//    function ($router) {
//        Route::apiResource('orders', OrderController::class);
//        Route::apiResource('order/items', OrderItemController::class);
//        Route::apiResource('favourites', FavouriteListController::class);
//        Route::apiResource('carts', CartController::class);
//
//    }
//);

// CUSTOMER
Route::group(
    [
        'middleware' => ['role:customer,admin,staff'],
    ],
    function ($router) {


        Route::post('carts', [CartController::class, 'store']);
        Route::get('carts', [CartController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show']);


        Route::post('carts', [CartController::class, 'addProductToCart'])->name('carts.store');

        Route::post('/carts', [CartController::class, 'addProductToCart']);
        Route::delete('/carts/{id}', [CartController::class, 'deleteProductFromCart']);
        Route::get('/carts', [CartController::class, 'listProductsInCart']);
        Route::delete('carts/{id}', [CartController::class, 'destroy'])->name('carts.destroy');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/user/comments', [UserCommentController::class, 'index']);
        Route::post('/user/comments', [UserCommentController::class, 'store']);
        Route::get('/user/comments/{id}', [UserCommentController::class, 'show']);
        Route::put('/user/comments/{id}', [UserCommentController::class, 'update']);
        Route::delete('/user/comments/{id}', [UserCommentController::class, 'destroy']);

        Route::get('/favourites', [FavouriteListController::class, 'index']);
        Route::post('/favourites', [FavouriteListController::class, 'store']);
        Route::delete('/favourites/{id}', [FavouriteListController::class, 'destroy']);

    }
);


