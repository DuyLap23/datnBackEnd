<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class VouCherController extends Controller
{
    public function index()
    {
        try {
            // Truy vấn tất cả vouchers có voucher_active = true
            $vouchers = Voucher::query()->where('voucher_active', true)->get();
    
            // Nếu không có voucher nào
            if ($vouchers->isEmpty()) {
                return response()->json([
                    'message' => 'Không có voucher nào hoạt động.'
                ], 404);
            }
    
            // Trả về danh sách voucher dưới dạng JSON
            return response()->json($vouchers, 200);
    
        } catch (Exception $e) {
            // Trả về thông báo lỗi nếu có ngoại lệ
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi truy xuất danh sách vouchers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    


public function store(Request $request)
{
    // Xác thực dữ liệu đầu vào
    $validator = Validator::make($request->all(), [
        'minimum_order_value' => 'required|numeric|min:1',
        'discount_type' => 'required|in:fixed,percent',
        'discount_value' => 'required|numeric|min:0',
        'start_date' => 'required|date|after_or_equal:' . Carbon::now()->format('Y-m-d'),
        'end_date' => 'required|date|after:start_date',
        'usage_limit' => 'required|integer|min:1',
        'voucher_active' => 'required|boolean',
    ]);
    
    // Nếu dữ liệu không hợp lệ, trả về lỗi
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    DB::beginTransaction(); // Bắt đầu giao dịch với cơ sở dữ liệu
    try {
        // Tạo chuỗi 10 ký tự viết hoa ngẫu nhiên cho tên voucher
        $name = Str::upper(Str::random(10));

        // Tạo một Voucher mới và lưu vào database
        $voucher = Voucher::create(array_merge($request->all(), ['name' => $name]));

        DB::commit(); // Commit giao dịch nếu không có lỗi

        // Trả về Voucher mới được tạo dưới dạng JSON với mã trạng thái 201
        return response()->json($voucher, 201);

    } catch (Exception $e) {
        DB::rollBack(); // Nếu có lỗi, rollback giao dịch
        // Xử lý lỗi và trả về thông báo lỗi
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi tạo voucher.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function show(Voucher $id)
    {
        DB::beginTransaction();
        try {
            // Trả về thông tin của Voucher được chỉ định dưới dạng JSON
            return response()->json($id, 200);
        } catch (Exception $e) {
            // Trả về thông báo lỗi nếu xảy ra lỗi
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi truy xuất voucher.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function update(Request $request, Voucher $voucher)
{
    $validator = Validator::make($request->all(), [
        'name' => ['required', Rule::unique('vouchers')->ignore($voucher->id)],
        'minimum_order_value' => 'required|numeric|min:1',
        'discount_type' => 'required|in:fixed,percent',
        'discount_value' => 'required|numeric|min:0',
        'start_date' => 'required|date|after_or_equal:' . Carbon::now()->format('Y-m-d'),
        'end_date' => 'required|date|after:start_date',
        'usage_limit' => 'required|integer|min:1',
        'voucher_active' => 'required|boolean',
    ]);

    // Nếu dữ liệu không hợp lệ, trả về lỗi
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    DB::beginTransaction();
    try {
        // Cập nhật thông tin Voucher
        $voucher->update($request->all());

        // Trả về Voucher đã được cập nhật dưới dạng JSON
        return response()->json($voucher);

    } catch (Exception $e) {
        // Xử lý lỗi và trả về thông báo lỗi
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi cập nhật voucher.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function destroy(Voucher $voucher)
{
    try {
        // Xóa Voucher
        $voucher->delete();

        // Trả về phản hồi thành công với mã trạng thái 204
        return response()->json(null, 204);

    } catch (Exception $e) {
        // Xử lý lỗi và trả về thông báo lỗi
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi xóa voucher.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function apply(Request $request)
{
    $validator = Validator::make($request->all(), [
        'code' => 'required',
        'order_total' => 'required|numeric|min:1',
    ]);

    // Nếu dữ liệu không hợp lệ, trả về lỗi
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Tìm voucher theo mã code
        $voucher = Voucher::where('name', $request->input('code'))->first();

        // Kiểm tra voucher có tồn tại không
        if (!$voucher) {
            return response()->json(['error' => 'Không tìm thấy voucher với mã này'], 404);
        }

        // Kiểm tra trạng thái kích hoạt
        if (!$voucher->voucher_active) {
            return response()->json(['error' => 'Voucher này không còn hiệu lực sử dụng'], 400);
        }

        // Kiểm tra thời hạn của voucher
        if ($voucher->start_date > Carbon::now() || $voucher->end_date < Carbon::now()) {
            return response()->json(['error' => 'Voucher này đã hết thời gian sử dụng'], 400);
        }

        // Kiểm tra giới hạn sử dụng
        if ($voucher->usage_limit <= $voucher->used_count) {
            return response()->json(['error' => 'Voucher này đã đạt giới hạn sử dụng'], 400);
        }

        // Kiểm tra giá trị đơn hàng có đủ điều kiện sử dụng voucher không
        if ($voucher->minimum_order_value > $request->input('order_total')) {
            return response()->json(['error' => 'Giá trị đơn hàng không đủ để sử dụng voucher này'], 400);
        }

        // Tất cả các điều kiện đều được
        $discountAmount = $this->calculateDiscountAmount($voucher, $request->input('order_total'));

        // Tăng số lần sử dụng của voucher lên 1
        $voucher->increment('used_count');

        // Trả về số tiền giảm giá và voucher đã được cập nhật
        return response()->json([
            'message' => 'Voucher đã được áp dụng thành công.',
            'discount_amount' => $discountAmount,
            'updated_voucher' => $voucher,
        ]);

    } catch (Exception $e) {
        // Xử lý lỗi và trả về thông báo lỗi
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi áp dụng voucher.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    private function calculateDiscountAmount(Voucher $voucher, float $orderTotal): float
    {
        // Tính toán số tiền giảm giá dựa trên loại và giá trị của Voucher
        if ($voucher->discount_type === 'percent') {
            // Giá trị giảm giá theo % s
            return $orderTotal * ($voucher->discount_value / 100);
        } else {
            // Giá trị giảm giá theo số tiền sẽ được lấy trực tiếp từ trường discount_value của voucher
            return $voucher->discount_value;
        }
    }
}
