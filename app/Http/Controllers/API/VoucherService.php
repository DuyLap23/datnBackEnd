<?php

namespace App\Http\Controllers\API;


use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class VoucherService
{
    public function apply(array $data): JsonResponse
    {
        Log::info('Checking voucher with data:', $data);

        $voucher = Voucher::where('code', $data['code'])
            ->where('voucher_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereRaw('used_count < usage_limit')->get();

        Log::info('Voucher query:', [
            'voucher' => $voucher
        ]);

        $voucher = $voucher->first();

        Log::info('Voucher found:', ['voucher' => $voucher]);

        if (!$voucher) {
            return response()->json([
                'error' => 'Voucher không tồn tại hoặc đã hết hạn'
            ], 404);
        }
        if (!isset($data['code']) || !isset($data['products']) || !isset($data['order_total'])) {
            return response()->json([
                'error' => 'Dữ liệu không hợp lệ'
            ], 400);
        }

        if (empty($data['products'])) {
            return response()->json([
                'error' => 'Không có sản phẩm nào trong đơn hàng'
            ], 400);
        }

        // Tính toán sản phẩm áp dụng được
        $applicableProducts = collect($data['products'])->filter(function ($product) use ($voucher) {
            try {
                $applicableIds = json_decode($voucher->applicable_ids, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Error decoding applicable_ids', [
                        'voucher_id' => $voucher->id,
                        'applicable_ids' => $voucher->applicable_ids,
                        'error' => json_last_error_msg()
                    ]);
                    return false;
                }

                return $voucher->applicable_type === 'product'
                    ? in_array($product['product_id'], $applicableIds)
                    : in_array($product['category_id'], $applicableIds);
            } catch (\Exception $e) {
                Log::error('Lỗi khi xử lý sản phẩm áp dụng', [
                    'error' => $e->getMessage(),
                    'voucher' => $voucher->toArray(),
                    'product' => $product
                ]);
                return false;
            }
        });
        $applicableTotal = $applicableProducts->sum(function ($product) {
            Log::info('', ['price' => $product['price']]);
            Log::info('', ['quantity' => $product['quantity']]);

//            return $product['price'] * $product['quantity'];
        });
        Log::info('', ['applicableTotal' => $applicableTotal]);
        Log::info('', ['minimum_order_value' => $voucher->minimum_order_value]);
        if ($applicableTotal < $voucher->minimum_order_value) {
            return response()->json([
                'error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher',
                'minimum_required' => $voucher->minimum_order_value,
                'current_total' => $applicableTotal,
                'difference' => $voucher->minimum_order_value - $applicableTotal
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
