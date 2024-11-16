<?php

namespace App\Http\Controllers\Api;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VoucherService
{
   public function apply(array $data): JsonResponse
   {
       try {
           if (!isset($data['code']) || !isset($data['products']) || !isset($data['order_total']) || empty($data['products'])) {
               return response()->json(['error' => 'Dữ liệu không hợp lệ'], 400);
           }

           $voucher = Voucher::where('code', $data['code'])
               ->where('voucher_active', true)
               ->whereDate('start_date', '<=', now())
               ->whereDate('end_date', '>=', now())
               ->where('used_count', '<', DB::raw('usage_limit'))
               ->first();

           if (!$voucher) {
               return response()->json(['error' => 'Voucher không tồn tại hoặc đã hết hạn'], 404);
           }

           // Calculate applicable products and total
           $applicableTotal = collect($data['products'])
               ->filter(function ($product) use ($voucher) {
                   $applicableIds = json_decode($voucher->applicable_ids, true) ?? [];
                   return $voucher->applicable_type === 'product' 
                       ? in_array($product['product_id'], $applicableIds)
                       : in_array($product['category_id'], $applicableIds);
               })
               ->sum(function ($product) {
                   return $product['price'] * $product['quantity'];
               });
              // kiểm tra giá trị tối thiểu
               Log::info('Voucher application', [
                'applicableTotal' => $applicableTotal,
                'minimum_order_value' => $voucher->minimum_order_value,
                'products' => $data['products']
            ]);
            if ($applicableTotal < $voucher->minimum_order_value) {
                return response()->json([
                    'error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher',
                    'minimum_required' => $voucher->minimum_order_value,
                    'current_total' => $applicableTotal,
                    'difference' => $voucher->minimum_order_value - $applicableTotal,
                    'voucher_details' => $voucher,
                    'products_data' => $data['products'], // Hiển thị thông tin sản phẩm
                    'applicable_type' => $voucher->applicable_type, // Loại áp dụng (product/category)
                    'applicable_ids' => json_decode($voucher->applicable_ids, true) // Danh sách IDs áp dụng
                ], 400);
            }
            

           // Calculate discount
           $discount = $voucher->discount_type === 'fixed' 
               ? $voucher->discount_value 
               : ($applicableTotal * $voucher->discount_value) / 100;

           if (isset($voucher->max_discount) && $voucher->max_discount > 0) {
               $discount = min($discount, $voucher->max_discount);
           }

           return response()->json([
               'success' => true,
               'discount_amount' => $discount,
               'voucher_details' => $voucher,
               'applicable_total' => $applicableTotal
           ]);

       } catch (\Exception $e) {
           Log::error('Voucher application error', ['error' => $e->getMessage(), 'data' => $data]);
           return response()->json(['error' => 'Có lỗi xảy ra khi áp dụng voucher'], 500);
       }
   }
}