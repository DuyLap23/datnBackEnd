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
            DB::beginTransaction();
    
            if (!isset($data['code']) || !isset($data['products']) || !isset($data['order_total']) || empty($data['products'])) {
                return response()->json(['error' => 'Dữ liệu không hợp lệ'], 400);
            }
    
            // Kiểm tra voucher còn hiệu lực và chưa hết lượt dùng
            $voucher = Voucher::where('code', $data['code'])
                ->where('voucher_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->where('used_count', '<', DB::raw('usage_limit'))
                ->lockForUpdate()
                ->first();
    
            if (!$voucher) {
                DB::rollBack();
                return response()->json(['error' => 'Voucher không tồn tại hoặc đã hết hạn'], 404);
            }
    
            // Tính tổng giá trị đơn hàng
            $orderTotal = collect($data['products'])
                ->sum(function ($product) {
                    return $product['price'] * $product['quantity'];
                });
    
            // Kiểm tra giá trị đơn hàng tối thiểu
            if ($orderTotal < $voucher->minimum_order_value) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher',
                    'minimum_required' => $voucher->minimum_order_value,
                    'current_total' => $orderTotal,
                    'difference' => $voucher->minimum_order_value - $orderTotal,
                    'voucher_details' => $voucher
                ], 400);
            }
    
            // Tính giá trị giảm giá
            $discount = $voucher->discount_type === 'fixed'
                ? $voucher->discount_value
                : ($orderTotal * $voucher->discount_value) / 100;
    
            // Áp dụng giới hạn giảm giá tối đa nếu có
            if ($voucher->max_discount !== null) {
                $discount = min($discount, $voucher->max_discount);
            }
    
            // Tăng số lần sử dụng
            $voucher->increment('used_count');
    
            // Kiểm tra và vô hiệu hóa voucher nếu đã hết lượt dùng
            if ($voucher->used_count >= $voucher->usage_limit) {
                $voucher->update(['voucher_active' => false]);
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'discount_amount' => $discount,
                'voucher_details' => $voucher->fresh(),
                'order_total' => $orderTotal,
                'max_discount_applied' => $voucher->max_discount !== null && $discount >= $voucher->max_discount,
                'remaining_uses' => max(0, $voucher->usage_limit - $voucher->used_count)
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher application error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return response()->json(['error' => 'Có lỗi xảy ra khi áp dụng voucher'], 500);
        }
    }
}
