<?php

namespace App\Http\Controllers\API;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VoucherService
{
   public function apply(array $data): JsonResponse
   {
       try {
           DB::beginTransaction(); // Bắt đầu transaction

           if (!isset($data['code']) || !isset($data['products']) || !isset($data['order_total']) || empty($data['products'])) {
               return response()->json(['error' => 'Dữ liệu không hợp lệ'], 400);
           }

           $voucher = Voucher::where('code', $data['code'])
               ->where('voucher_active', true)
               ->whereDate('start_date', '<=', now())
               ->whereDate('end_date', '>=', now())
               ->where('used_count', '<', DB::raw('usage_limit'))
               ->lockForUpdate() // Thêm lock để tránh race condition
               ->first();

           if (!$voucher) {
               DB::rollBack();
               return response()->json(['error' => 'Voucher không tồn tại hoặc đã hết hạn'], 404);
           }

           $applicableIds = json_decode($voucher->applicable_ids, true) ?? [];

           // Calculate applicable products and total
           $applicableTotal = collect($data['products'])
               ->filter(function ($product) use ($voucher, $applicableIds) {
                   if ($voucher->applicable_type === 'product') {
                       return in_array($product['product_id'], $applicableIds);
                   } else { // category type
                       return in_array($product['product_id'], $applicableIds);
                   }
               })
               ->sum(function ($product) {
                   return $product['price'] * $product['quantity'];
               });

           Log::info('Voucher application check', [
               'voucher_type' => $voucher->applicable_type,
               'applicable_ids' => $applicableIds,
               'products' => $data['products'],
               'applicableTotal' => $applicableTotal
           ]);

           if ($applicableTotal < $voucher->minimum_order_value) {
               DB::rollBack();
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

           // Tăng used_count lên 1
           $voucher->increment('used_count');
           
           // Kiểm tra nếu đã đạt giới hạn sử dụng thì tự động vô hiệu hóa voucher
           if ($voucher->used_count >= $voucher->usage_limit) {
               $voucher->update(['voucher_active' => false]);
           }

           DB::commit(); // Commit transaction nếu mọi thứ OK

           return response()->json([
               'success' => true,
               'discount_amount' => $discount,
               'voucher_details' => $voucher->fresh(), // Lấy dữ liệu mới nhất của voucher
               'applicable_total' => $applicableTotal,
               'applicable_products' => collect($data['products'])
                   ->filter(function ($product) use ($voucher, $applicableIds) {
                       return in_array($product['product_id'], $applicableIds);
                   })->values()->all()
           ]);

       } catch (\Exception $e) {
           DB::rollBack(); // Rollback nếu có lỗi
           Log::error('Voucher application error', ['error' => $e->getMessage(), 'data' => $data]);
           return response()->json(['error' => 'Có lỗi xảy ra khi áp dụng voucher'], 500);
       }
   }
}