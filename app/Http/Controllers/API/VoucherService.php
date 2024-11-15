<?php

namespace App\Http\Controllers\Api;

use App\Models\Voucher;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class VoucherService
{
    public function apply(array $data): JsonResponse
    {
        $voucher = Voucher::where('code', $data['code'])
            ->where('voucher_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('used_count', '<', 'usage_limit')
            ->first();

        if (!$voucher) {
            return response()->json([
                'error' => 'Voucher không tồn tại hoặc đã hết hạn'
            ], 404);
        }

        // Tính toán sản phẩm áp dụng được
        $applicableProducts = collect($data['products'])->filter(function ($product) use ($voucher) {
            $applicableIds = json_decode($voucher->applicable_ids);
            return $voucher->applicable_type === 'product' 
                ? in_array($product['product_id'], $applicableIds)
                : in_array($product['category_id'], $applicableIds);
        });

        $applicableTotal = $applicableProducts->sum(function ($product) {
            return $product['price'] * $product['quantity'];
        });

        if ($applicableTotal < $voucher->minimum_order_value) {
            return response()->json([
                'error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher',
                'minimum_required' => $voucher->minimum_order_value
            ], 400);
        }

        // Tính số tiền giảm
        $discount = $this->calculateDiscount($voucher, $applicableTotal);

        return response()->json([
            'discount_amount' => $discount,
            'voucher_details' => $voucher
        ]);
    }

    private function calculateDiscount($voucher, $total)
    {
        if ($voucher->discount_type === 'fixed') {
            return $voucher->discount_value;
        }

        $discount = ($total * $voucher->discount_value) / 100;
        
        if ($voucher->max_discount > 0) {
            return min($discount, $voucher->max_discount);
        }

        return $discount;
    }
}