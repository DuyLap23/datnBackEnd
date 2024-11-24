<?php

namespace App\Http\Controllers\API\Statistical;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderStatisticalController extends Controller
{
    public function order(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $latestOrders = Order::with(['user', 'orderItems.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->limit(5)
            ->get();

        if ($latestOrders->isEmpty()) {
            return response()->json([
                'message' => 'Không có đơn hàng nào trong khoảng thời gian này.',
                'data' => []
            ], 200);
        }

        $formattedOrders = $latestOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email
                ],
                'total_amount' => number_format($order->total_amount, 0, ',', '.') . 'đ',
                'order_status' => $order->order_status,
                'created_at' => $order->created_at->format('H:i:s d-m-Y '),
                'order_items' => $order->orderItems->map(function ($detail) {
                    return [
                        'product_name' => Str::limit($detail->product->name, 20),
                        'quantity' => $detail->quantity,
                        'price' => number_format($detail->price, 0, ',', '.') . 'đ',
                        'subtotal' => number_format($detail->quantity * $detail->price, 0, ',', '.') . 'đ',
                    ];
                })
            ];
        });

        Log::info('', ['latest_orders' => $formattedOrders]);

        return response()->json($formattedOrders);
    }

}
