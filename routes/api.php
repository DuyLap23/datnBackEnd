<?php

use App\Http\Controllers\API\ProductShowHomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\VouCherController;
use App\Http\Controllers\API\Auth\ResetPassword;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\BannerMktController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Order\OrderController;
use App\Http\Controllers\API\ProductSizeController;
use App\Http\Controllers\Api\UserCommentController;
use App\Http\Controllers\API\ProductColorController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\FavouriteListController;
use App\Http\Controllers\API\Search\FilterController;
use App\Http\Controllers\API\Search\SearchController;
use App\Http\Controllers\API\Order\DeliveryController;
use App\Http\Controllers\API\ProductVariantController;
use App\Http\Controllers\API\Order\OrderTrackingController;
use App\Http\Controllers\API\Order\OrderManagementController;
use App\Http\Controllers\API\Order\OrderUserManagementController;
use App\Http\Controllers\API\Statistical\UserStatisticalController;
use App\Http\Controllers\API\Statistical\OrderStatisticalController;
use App\Http\Controllers\API\Statistical\ProductStatisticalController;
use App\Http\Controllers\API\Statistical\RevenueStatisticalController;


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
        'middleware' => ['auth:api'],
    ],
    function ($router) {
        Route::post('products/{id_product}/comments', [CommentController::class, 'store']);
        Route::get('/comments/{id}', [CommentController::class, 'showUserComment']);
        Route::patch('products/{id_product}/comments', [CommentController::class, 'update']);
        Route::delete('products/{id}/comments', [CommentController::class, 'userDestroy']);
        Route::apiResource('addresses', AddressController::class);
        Route::put('addresses/{id}/default', [AddressController::class, 'setDefault'])->name('addresses.setDefault');


    }
);

//Những đầu route không cần check đăng nhập và role vất vào đây
Route::get('get-categories', [CategoryController::class, 'getCategoryChild']);
Route::get('filter-categories', [CategoryController::class, 'fillerCategory']);
Route::get('categories/{id}', [CategoryController::class, 'showClient']);

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class,'show'])->name('products.show');

Route::get('filter', [FilterController::class, 'filter'])->name('filter');
Route::get('search', [SearchController::class, 'search'])->name('search');

Route::get('get/comments/{product_id}', [CommentController::class, 'getCommentsByProduct']);

Route::get('vnpay/return',[OrderController::class, 'paymentReturn'])->name('vnpay.return');

Route::get('voucher/{id}', [VouCherController::class, 'show']);
Route::post('voucher/apply', [VouCherController::class, 'apply']);
Route::get('home/banners', [BannerMktController::class, 'getHomeBanners'])->name('home.banners');



Route::get('top-selling', [ProductShowHomeController::class, 'topSellingProducts'])->name('topSelling');
Route::get('top-view', [ProductShowHomeController::class, 'getTopViewedProducts'])->name('topView');



//ADMIN
Route::group(
    [
        'middleware' => ['role:admin', 'admin'],
        'prefix' => 'admin',
    ],
    function ($router) {
        //CATEGORIES
        Route::get('categories/trashed', [CategoryController::class, 'trashed']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
//        Route::get('categories/search', [CategoryController::class, 'searchCategory']);


        Route::apiResource('brands', BrandController::class);
        Route::apiResource('tags', TagController::class);

        Route::get('orders/ready-to-deliver', [DeliveryController::class, 'index']);
        Route::post('orders/confirm/{id}', [DeliveryController::class, 'confirmOrder']);
        Route::post('orders/confirm-delivery/{id}', [DeliveryController::class, 'confirmDelivery']);
        Route::post('orders/update-status/{id}', [DeliveryController::class, 'updateDeliveryStatus']);

        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{slug}', [ProductController::class, 'update']);
        Route::put('products/{id}/toggle-active', [ProductController::class, 'toggleActive']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::apiResource('product/colors', ProductColorController::class);
        Route::apiResource('product/sizes', ProductSizeController::class);
        Route::apiResource('product/variants', ProductVariantController::class);
        Route::get('products/search', [ProductController::class, 'searchProduct']);

        Route::get('users', [UserController::class, 'index']);

        // Comments routes
        Route::get('comments', [CommentController::class, 'index']);
        Route::delete('comments/{id}', [CommentController::class, 'destroy']);

        Route::get('banners', [BannerMktController::class, 'index']);
        Route::post('banners', [BannerMktController::class, 'store']);
        Route::post('banners/update/{id}', [BannerMktController::class, 'update']);
        Route::get('banners/{id}', [BannerMktController::class, 'show']);
        Route::delete('banners/delete/{id}', [BannerMktController::class, 'destroy']);

        Route::get('/orders/order-status-tracking', [OrderTrackingController::class, 'index']);
        Route::get('/orders', [OrderManagementController::class, 'index']);
        Route::get('/orders/filter', [OrderManagementController::class, 'filterByDate']);
        Route::get('/orders/search', [OrderManagementController::class, 'search']);
        Route::get('/orders/{id}', [OrderManagementController::class, 'detall']);
        Route::put('/orders/status/{id}', [OrderManagementController::class, 'updateStatus']);
        Route::post('/orders/{id}/refund', [OrderManagementController::class, 'refund']);
        Route::delete('/orders/{id}', [OrderManagementController::class, 'destroy']);

        // VouCher
       Route::post('voucher', [VouCherController::class, 'store']);
       Route::put('voucher/{id}', [VouCherController::class, 'update']);
       Route::delete('voucher/{id}', [VouCherController::class, 'destroy']);
       Route::get('getAllVouchers', [VouCherController::class, 'getAllVouchers']);
       Route::get('vouchers/search', [VouCherController::class, 'searchVoucher']);

       Route::get('statistical/revenue', [RevenueStatisticalController::class, 'revenue'])->name('statisticalRevenue');
       Route::get('statistical/monthly-revenue', [RevenueStatisticalController::class, 'monthlyRevenue'])->name('statisticalMonthlyRevenue');
       Route::get('statistical/order', [OrderStatisticalController::class, 'order'])->name('statisticalOrder');
       Route::get('statistical/user', [UserStatisticalController::class, 'user'])->name('statisticalUser');
       Route::get('statistical/product', [ProductStatisticalController::class, 'product'])->name('statisticalProduct');

    }
);

// CUSTOMER
Route::group(
    [
        'middleware' => ['role:customer,admin'],
    ],
    function ($router) {
        Route::post('carts', [CartController::class, 'addProductToCart']);
        Route::delete('carts/{id}', [CartController::class, 'deleteProductFromCart']);
        Route::get('carts', [CartController::class, 'listProductsInCart']);
        Route::patch('cart/{id}', [CartController::class, 'updateCartItemQuantity']);

        Route::put('user/comments/{id}', [UserCommentController::class, 'update']);
        Route::delete('user/comments/{id}', [UserCommentController::class, 'destroy']);

        Route::get('favourites', [FavouriteListController::class, 'index']);
        Route::post('favourites', [FavouriteListController::class, 'store']);
        Route::delete('favourites/{id}', [FavouriteListController::class, 'destroy']);

        Route::post('orders', [OrderController::class, 'order']);

        Route::get('user/orders', [OrderUserManagementController::class, 'index']);
        Route::patch('user/orders/{id}/cancel', [OrderUserManagementController::class, 'cancelOrder']);
        Route::patch('user/orders/address', [OrderUserManagementController::class, 'updateAddress']);
        Route::patch('user/orders/{id}/payment-method', [OrderUserManagementController::class, 'updatePaymentMethod']);
        Route::get('user/orders/{id}', [OrderUserManagementController::class, 'show']);
        Route::patch('user/orders/mark-as-received/{id}', [OrderUserManagementController::class, 'markAsReceived']);

        Route::get('voucher', [VouCherController::class, 'index']);
    }
);
