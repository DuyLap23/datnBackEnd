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
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date'
        ]);
        if (!$request->start_date || !$request->end_date) {
            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $days->put($date, 0); // Khởi tạo giá trị mặc định là 0
            }

            $revenueByDay = Order::query()
                ->where('order_status', 'completed')
                ->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total_revenue')
                ->groupBy('date')
                ->get()
                ->pluck('total_revenue', 'date');

            $result = $days->merge($revenueByDay)->sortKeys();

            // Chuyển đổi thành mảng các object {time, price}
            $formattedResult = $result->map(function ($value, $date) {
                return [
                    'time' => $date,
                    'price' => number_format($value, 0, ',', '.') . 'đ'
                ];
            })->values(); // Dùng values() để chuyển collection thành mảng tuần tự

            return response()->json([
                'total_revenue_default' => $formattedResult,
                'total_revenue' => number_format($result->sum(), 0, ',', '.') . 'đ',
            ]);
        }

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
