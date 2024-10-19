<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function order () {
        try {
        DB::transaction(function () {

            $user = auth('api')->user();

            if(!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
                ], 401);
            }

            $totalAmount = 0;
            $dataItem = [];

            $cart = Cart::query()->where('user_id', $user->id)->get();

            foreach ($cart as $cartItem){
                $totalAmount += $cartItem->quantity * ($cartItem->product->price_sale ?? $cartItem->product->price_regular);

                $dataItem [] =[
                    'product_id' => $cartItem->product_id,
                    'color' => $cartItem->color,
                    'size' => $cartItem->size,
                    'quantity' => $cartItem->quantity,
                    'price'=> $cartItem->product->price_sale ?: $cartItem->product->price_regular
                ];
            }
            if (!$user->address_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần thêm địa chỉ trước khi đặt hàng.'
                ], 400);
            }
            $order = Order::query()->create([
                'user_id' => $user->id,
                'address_id' => $user->address_id,
                'payment_method' => \request('payment_method'),
                'payment_status' => \request('payment_status'),
                'order_status' => \request('order_status'),
                'total_amount'=> $totalAmount,
                'note' => \request('note'),

            ]);
            foreach ($dataItem as $item) {
                $item['order_id'] = $order->id;
                OrderItem::query()->create($item);
            }
            CartItem::where('user_id', $user->id)->delete();

        });
            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công.',
            ],201);
        } catch (Exception $e) {
            Log::error('Đặt hàng không thành công: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể đặt hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
