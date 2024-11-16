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

           $applicableIds = json_decode($voucher->applicable_ids, true) ?? [];
           
           // Calculate applicable products and total
           $applicableTotal = collect($data['products'])
               ->filter(function ($product) use ($voucher, $applicableIds) {
                   if ($voucher->applicable_type === 'product') {
                       return in_array($product['product_id'], $applicableIds);
                   } else { // category type
                       // Nếu product_id nằm trong danh sách applicable_ids (đã được lưu khi tạo voucher)
                       return in_array($product['product_id'], $applicableIds);
                   }
               })
               ->sum(function ($product) {
                   return $product['price'] * $product['quantity'];
               });

           // Log thông tin để debug
           Log::info('Voucher application check', [
               'voucher_type' => $voucher->applicable_type,
               'applicable_ids' => $applicableIds,
               'products' => $data['products'],
               'applicableTotal' => $applicableTotal
           ]);

           if ($applicableTotal < $voucher->minimum_order_value) {
               return response()->json([
                   'error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher',
                   'minimum_required' => $voucher->minimum_order_value,
                   'current_total' => $applicableTotal,
                   'difference' => $voucher->minimum_order_value - $applicableTotal,
                   'voucher_details' => $voucher,
                   'applicable_products' => collect($data['products'])
                       ->filter(function ($product) use ($voucher, $applicableIds) {
                           return in_array($product['product_id'], $applicableIds);
                       })->values()->all(),
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
               'applicable_total' => $applicableTotal,
               'applicable_products' => collect($data['products'])
                   ->filter(function ($product) use ($voucher, $applicableIds) {
                       return in_array($product['product_id'], $applicableIds);
                   })->values()->all()
           ]);

       } catch (\Exception $e) {
           Log::error('Voucher application error', ['error' => $e->getMessage(), 'data' => $data]);
           return response()->json(['error' => 'Có lỗi xảy ra khi áp dụng voucher'], 500);
       }
   }
}