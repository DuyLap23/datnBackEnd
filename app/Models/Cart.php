<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_id', 'quantity','color','size','price','product_variant_id'
    ];

    protected $casts = [
        'price' => 'float',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id'); 
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}



//  public function index(Request $request)
//      {
//          try {
//              $user = auth('api')->user();
//              if (!$user) {
//                  return response()->json([
//                      'success' => false,
//                      'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
//                  ], 401);
//              }
     
//              // Join với bảng products để lấy thông tin category
//              $cartItems = Cart::where('user_id', $user->id)
//                  ->join('products', 'carts.product_id', '=', 'products.id')
//                  ->select(
//                      'carts.*',
//                      'products.name',
//                      'products.price_regular',
//                      'products.price_sale',
//                      'products.category_id'
//                  )
//                  ->get();
     
//              if ($cartItems->isEmpty()) {
//                  return response()->json([
//                      'success' => false,
//                      'message' => 'Giỏ hàng trống.'
//                  ], 404);
//              }
     
//              $cartProducts = [];
//              $cartTotal = 0;
//              $cartCategories = []; 
     
//              foreach ($cartItems as $item) {
//                  $price = $item->price_sale ?? $item->price_regular;
//                  $cartTotal += $price * $item->quantity;
                 
//                  if ($item->category_id) {
//                      $cartCategories[] = $item->category_id;
//                  }
     
//                  $cartProducts[] = [
//                      'product_id' => $item->product_id,
//                      'name' => $item->name,
//                      'price' => $price,
//                      'quantity' => $item->quantity,
//                      'category_id' => $item->category_id
//                  ];
//              }
     
//              $cartCategories = array_unique($cartCategories);
     
//              $now = Carbon::now();
             
//              $vouchersQuery = Voucher::where('voucher_active', true)
//                  ->where('start_date', '<=', $now)
//                  ->where('end_date', '>=', $now)
//                  ->where('usage_limit', '>', DB::raw('used_count'))
//                  ->where('minimum_order_value', '<=', $cartTotal);
     
//              $vouchers = $vouchersQuery->get();
     
//              // Chỉnh sửa logic filter voucher
//              $applicableVouchers = $vouchers->filter(function ($voucher) use ($cartProducts, $cartTotal, $cartCategories) {
//                  $voucherApplicableIds = json_decode($voucher->applicable_ids, true);
                 
//                  // Kiểm tra lỗi JSON decode
//                  if (json_last_error() !== JSON_ERROR_NONE) {
//                      Log::error('JSON decode error for voucher ' . $voucher->id, [
//                          'error' => json_last_error_msg(),
//                          'applicable_ids' => $voucher->applicable_ids
//                      ]);
//                      return false;
//                  }
     
//                  // Debug log
//                  Log::info('Voucher Filtering Debug', [
//                      'voucher_id' => $voucher->id,
//                      'applicable_type' => $voucher->applicable_type,
//                      'applicable_ids' => $voucherApplicableIds,
//                      'cart_categories' => $cartCategories,
//                      'cart_products' => array_column($cartProducts, 'product_id')
//                  ]);
     
//                  switch ($voucher->applicable_type) {
//                      case 'product':
//                          // Chỉ áp dụng nếu có sản phẩm trong giỏ hàng trùng với applicable_ids
//                          $matchedProducts = array_filter($cartProducts, function($product) use ($voucherApplicableIds) {
//                              return in_array($product['product_id'], $voucherApplicableIds);
//                          });
//                          return !empty($matchedProducts);
     
//                      case 'category':
//                          // Chỉ áp dụng nếu có sản phẩm thuộc category của voucher
//                          $matchedCategories = array_intersect($cartCategories, $voucherApplicableIds);
//                          return !empty($matchedCategories);
     
//                      default:
//                          return false;
//                  }
//              })->map(function ($voucher) use ($cartProducts, $cartTotal, $cartCategories) {
//                  $voucherApplicableIds = json_decode($voucher->applicable_ids, true);
//                  $applicable = false;
//                  $applicableProducts = [];
     
//                  switch ($voucher->applicable_type) {
//                      case 'category':
//                          $matchingCategories = array_intersect($cartCategories, $voucherApplicableIds);
                         
//                          if (!empty($matchingCategories)) {
//                              $applicable = true;
//                              $applicableProducts = $cartProducts;
//                          }
//                          break;
     
//                      case 'product':
//                          foreach ($cartProducts as $product) {
//                              if (in_array($product['product_id'], $voucherApplicableIds)) {
//                                  $applicable = true;
//                                  $applicableProducts[] = $product;
//                              }
//                          }
//                          break;
//                  }
     
//                  if (!$applicable || empty($applicableProducts)) {
//                      return null;
//                  }
     
//                  $applicableTotal = array_sum(array_map(function ($product) {
//                      return $product['price'] * $product['quantity'];
//                  }, $applicableProducts));
     
//                  $discountAmount = 0;
//                  if ($voucher->discount_type === 'percent') {
//                      $discountAmount = ($applicableTotal * $voucher->discount_value) / 100;
//                      if ($voucher->max_discount > 0) {
//                          $discountAmount = min($discountAmount, $voucher->max_discount);
//                      }
//                  } else {
//                      $discountAmount = min($voucher->discount_value, $applicableTotal);
//                  }
     
//                  return [
//                      'id' => $voucher->id,
//                      'code' => $voucher->code,
//                      'name' => $voucher->name,
//                      'discount_type' => $voucher->discount_type,
//                      'discount_value' => $voucher->discount_value,
//                      'minimum_order_value' => $voucher->minimum_order_value,
//                      'potential_discount' => $discountAmount,
//                      'applicable_products' => $applicableProducts,
//                      'max_discount' => $voucher->max_discount
//                  ];
//              })->filter();
     
//              return response()->json([
//                  'success' => true,
//                  'message' => 'Lấy danh sách voucher thành công',
//                  'data' => [
//                      'cart_total' => $cartTotal,
//                      'vouchers' => $applicableVouchers->values(),
//                      'total_available_vouchers' => $applicableVouchers->count(),
//                      'cart_categories' => $cartCategories
//                  ]
//              ]);
//          } catch (Exception $e) {
//              Log::error('Lỗi khi lấy danh sách voucher:', [
//                  'error' => $e->getMessage(),
//                  'trace' => $e->getTraceAsString()
//              ]);
     
//              return response()->json([
//                  'success' => false,
//                  'message' => 'Đã có lỗi xảy ra khi lấy danh sách voucher',
//                  'error' => $e->getMessage()
//              ], 500);
//          }
//      }