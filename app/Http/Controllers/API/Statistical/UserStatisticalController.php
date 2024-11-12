<?php

namespace App\Http\Controllers\API\Statistical;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserStatisticalController extends Controller
{
    public function user(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        Log::info('', ['start_date' => $startDate, 'end_date' => $endDate]);
        $topCustomers = Order::query()
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_spent')
            )
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', 'completed')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'total_orders' => $customer->total_orders,
                    'total_spent' => $customer->total_spent,
                    'average_order_value' => $customer->total_orders > 0
                        ? $customer->total_spent / $customer->total_orders
                        : 0
                ];
            });
        return response()->json([
            'top_customers' => $topCustomers

        ]);
    }
}
