<?php

namespace App\Http\Controllers\API\Statistical;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevenueStatisticalController extends Controller
{
    public function revenue(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        Log::info('', ['start_date' => $startDate, 'end_date' => $endDate]);
        // 1. Tổng doanh thu trong khoảng thời gian
        $totalRevenue = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('order_status', 'completed')
            ->sum('total_amount');
        Log::info('', ['total_revenue' => $totalRevenue]);
        return response()->json([
            'total_revenue' => number_format($totalRevenue, 0, ',', '.') . 'đ',
            'start_date' => date_format($startDate, 'Y-m-d'),
            'end_date' => date_format($endDate, 'Y-m-d'),
        ]);
    }
}
