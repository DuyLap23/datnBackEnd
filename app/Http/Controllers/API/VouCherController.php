<?php

namespace App\Http\Controllers\API;


use Exception;
use Carbon\Carbon;
use App\Models\Cart;

use App\Models\Product;
use App\Models\Voucher;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class VouCherController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    /**
     * @OA\Get(
     *     path="/api/vouchers",
     *     tags={"Vouchers"},
     *     summary="Lấy danh sách voucher có thể áp dụng cho giỏ hàng",
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Lọc voucher theo trạng thái (active/inactive)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Lọc voucher theo loại giảm giá",
     *         required=false,
     *         @OA\Schema(type="string", enum={"fixed", "percentage"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công"
     *     )
     * )
     */
    public function index(Request $request)
{
    try {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
            ], 401);
        }

        // Join với bảng products và categories, kiểm tra soft delete
        $cartItems = Cart::where('user_id', $user->id)
            ->join('products', 'carts.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('categories.deleted_at') // Chỉ lấy các sản phẩm có category chưa bị xóa
            ->select(
                'carts.*',
                'products.name',
                'products.price_regular',
                'products.price_sale',
                'products.category_id'
            )
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng trống.'
            ], 404);
        }

        // Tính tổng giá trị giỏ hàng
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $price = $item->price_sale ?? $item->price_regular;
            $cartTotal += $price * $item->quantity;
        }

        // Query voucher còn hoạt động và chưa hết lượt sử dụng
        $now = Carbon::now();
        $vouchers = Voucher::where('voucher_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('usage_limit', '>', DB::raw('used_count'))
            ->where('minimum_order_value', '<=', $cartTotal)
            ->get();

        $applicableVouchers = $vouchers->map(function ($voucher) use ($cartTotal, $cartItems) {
            // Kiểm tra nếu voucher có applicable_type là category
            if ($voucher->applicable_type === 'category') {
                $applicableIds = json_decode($voucher->applicable_ids, true);
                // Kiểm tra xem category có bị soft delete không
                $validCategories = Category::whereIn('id', $applicableIds)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();
                
                if (empty($validCategories)) {
                    return null;
                }
            }

            // Tính giá trị giảm giá
            $discountAmount = 0;
            if ($voucher->discount_type === 'percentage') {
                $discountAmount = ($cartTotal * $voucher->discount_value) / 100;
                if ($voucher->max_discount > 0) {
                    $discountAmount = min($discountAmount, $voucher->max_discount);
                }
            } else {
                $discountAmount = $voucher->discount_value;
            }

            return [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'discount_type' => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
                'max_discount' => $voucher->max_discount,
                'potential_discount' => $discountAmount,
                'minimum_order_value' => $voucher->minimum_order_value,
                'remaining_uses' => $voucher->usage_limit - $voucher->used_count
            ];
        })->filter(); // Loại bỏ các voucher null

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách voucher thành công',
            'data' => [
                'cart_total' => $cartTotal,
                'vouchers' => $applicableVouchers,
                'total_available_vouchers' => $applicableVouchers->count()
            ]
        ]);
    } catch (Exception $e) {
        Log::error('Lỗi khi lấy danh sách voucher:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Đã có lỗi xảy ra khi lấy danh sách voucher',
            'error' => $e->getMessage()
        ], 500);
    }
}
    



    /**
     * @OA\Post(
     *     path="/api/vouchers",
     *     summary="Tạo voucher mới",
     *     description="Thêm voucher mới vào hệ thống. Người dùng phải có quyền admin.",
     *     tags={"Voucher"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "discount_value", "start_date", "end_date"},
     *             @OA\Property(property="name", type="string", example="Mã khuyến mãi 20%"),
     *             @OA\Property(property="discount_type", type="string", enum={"fixed", "percent"}, example="percent"),
     *             @OA\Property(property="discount_value", type="number", format="float", example=20),
     *             @OA\Property(property="minimum_order_value", type="number", format="float", example=500000),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2024-11-01T00:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2024-12-01T23:59:59Z"),
     *             @OA\Property(property="usage_limit", type="integer", example=100),
     *             @OA\Property(property="applicable_type", type="string", enum={"product", "category"}, example="product"),
     *             @OA\Property(property="applicable_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tạo voucher thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Voucher đã được tạo."),
     *             @OA\Property(property="voucher", ref="#/components/schemas/Voucher")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền tạo voucher.")
     *         )
     *     )
     * )
     */

     public function store(Request $request)
     {
         $currentUser = auth('api')->user();
         if (!$currentUser || !$currentUser->isAdmin()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Bạn không phải admin.'
             ], 403);
         }
     
         // Validation rules đơn giản hóa
         $rules = [
             'name' => 'required|string|max:255',
             'minimum_order_value' => 'required|numeric|min:0',
             'discount_type' => 'required|in:fixed,percent',
             'start_date' => 'required|date|after_or_equal:today',
             'end_date' => 'required|date|after:start_date',
             'usage_limit' => 'required|integer|min:1',
             'voucher_active' => 'required|boolean'
         ];
     
         if ($request->discount_type === 'percent') {
            $rules['discount_value'] = 'required|numeric|min:0|max:100';
            $rules['max_discount'] = 'required|numeric|min:0';
        } else {
            $rules['discount_value'] = 'required|numeric|min:1000';
            $rules['max_discount'] = 'required|numeric|min:0';  // Thay đổi thành required
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Thêm kiểm tra logic cho max_discount
        if ($request->discount_type === 'fixed') {
            if ($request->max_discount > $request->minimum_order_value) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => [
                        'max_discount' => ['Giá trị giảm tối đa không được lớn hơn giá trị đơn hàng tối thiểu']
                    ]
                ], 422);
            }
            // Đảm bảo max_discount không nhỏ hơn discount_value
            if ($request->max_discount < $request->discount_value) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => [
                        'max_discount' => ['Giá trị giảm tối đa không được nhỏ hơn giá trị giảm']
                    ]
                ], 422);
            }
        }
     
         $validator = Validator::make($request->all(), $rules);
     
         if ($validator->fails()) {
             return response()->json([
                 'message' => 'Dữ liệu không hợp lệ',
                 'errors' => $validator->errors()
             ], 422);
         }
     
         // Kiểm tra logic bổ sung cho giá trị giảm giá
         if ($request->discount_type === 'fixed' && $request->discount_value > $request->minimum_order_value) {
             return response()->json([
                 'message' => 'Dữ liệu không hợp lệ',
                 'errors' => [
                     'discount_value' => ['Giá trị giảm giá không được lớn hơn giá trị đơn hàng tối thiểu']
                 ]
             ], 422);
         }
     
         DB::beginTransaction();
         try {
             // Tạo mã voucher
             $code = strtoupper(Str::random(10));
             while (Voucher::where('code', $code)->exists()) {
                 $code = strtoupper(Str::random(10));
             }
     
             // Tạo voucher mới
             $voucher = Voucher::create([
                 'name' => $request->name,
                 'code' => $code,
                 'minimum_order_value' => $request->minimum_order_value,
                 'discount_type' => $request->discount_type,
                 'discount_value' => $request->discount_value,
                 'max_discount' => $request->max_discount,
                 'start_date' => $request->start_date,
                 'end_date' => $request->end_date,
                 'usage_limit' => $request->usage_limit,
                 'used_count' => 0,
                 'voucher_active' => $request->voucher_active
             ]);
     
             DB::commit();
             return response()->json([
                 'message' => 'Tạo voucher thành công',
                 'data' => $voucher
             ], 201);
         } catch (Exception $e) {
             DB::rollBack();
             return response()->json([
                 'message' => 'Đã xảy ra lỗi khi tạo voucher',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
    /**
     * @OA\Get(
     *     path="/api/vouchers/{id}",
     *     summary="Chi tiết voucher",
     *     description="Xem thông tin chi tiết về một voucher cụ thể bằng ID.",
     *     tags={"Voucher"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của voucher cần xem",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chi tiết voucher",
     *         @OA\JsonContent(ref="#/components/schemas/Voucher")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voucher không tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Không tìm thấy voucher.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $voucher = Voucher::findOrFail($id);
            
            // Kiểm tra nếu voucher áp dụng cho category
            if ($voucher->applicable_type === 'category') {
                $applicableIds = json_decode($voucher->applicable_ids, true);
                $validCategories = Category::whereIn('id', $applicableIds)
                    ->whereNull('deleted_at')
                    ->get();
    
                if ($validCategories->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voucher này không còn hiệu lực do các danh mục áp dụng đã bị xóa.'
                    ], 400);
                }
    
                // Thêm thông tin về các category hợp lệ
                $voucher->valid_categories = $validCategories;
            }
            
            // Thêm thông tin về trạng thái hiện tại của voucher
            $now = Carbon::now();
            $voucher->current_status = $this->getVoucherStatus($voucher, $now);
            $voucher->remaining_uses = $voucher->usage_limit - $voucher->used_count;
    
            return response()->json([
                'success' => true,
                'data' => $voucher
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi truy xuất thông tin voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/vouchers/{id}",
     *     summary="Cập nhật voucher",
     *     description="Cập nhật thông tin voucher đã tồn tại. Yêu cầu quyền admin.",
     *     tags={"Voucher"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của voucher cần cập nhật",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Cập nhật khuyến mãi 20%"),
     *             @OA\Property(property="discount_type", type="string", enum={"fixed", "percent"}, example="fixed"),
     *             @OA\Property(property="discount_value", type="number", format="float", example=50000),
     *             @OA\Property(property="minimum_order_value", type="number", format="float", example=300000),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2024-11-01T00:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2024-12-31T23:59:59Z"),
     *             @OA\Property(property="usage_limit", type="integer", example=50),
     *             @OA\Property(property="applicable_type", type="string", enum={"product", "category"}, example="category"),
     *             @OA\Property(property="applicable_ids", type="array", @OA\Items(type="integer"), example={4, 5, 6})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật voucher thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Voucher đã được cập nhật."),
     *             @OA\Property(property="voucher", ref="#/components/schemas/Voucher")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voucher không tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Không tìm thấy voucher.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền cập nhật voucher.")
     *         )
     *     )
     * )
     */

     public function update(Request $request, $id)
     {
         $currentUser = auth('api')->user();
         if (!$currentUser || !$currentUser->isAdmin()) {
             return response()->json([
                 'success' => false,
                 'message' => 'Bạn không phải admin.'
             ], 403);
         }
     
         // Tìm voucher
         $voucher = Voucher::find($id);
         if (!$voucher) {
             return response()->json([
                 'message' => 'Không tìm thấy voucher'
             ], 404);
         }
     
         // Validation rules cơ bản
         $rules = [
             'name' => 'required|string|max:255',
             'minimum_order_value' => 'required|numeric|min:0',
             'discount_type' => 'required|in:fixed,percent',
             'start_date' => [
                 'required',
                 'date',
                 'after_or_equal:today',
                 function ($attribute, $value, $fail) use ($voucher) {
                     // Nếu voucher đã được sử dụng, không cho phép thay đổi ngày bắt đầu
                     if ($voucher->used_count > 0 && $value != $voucher->start_date) {
                         $fail('Không thể thay đổi ngày bắt đầu của voucher đã được sử dụng.');
                     }
                 }
             ],
             'end_date' => [
                 'required',
                 'date',
                 'after:start_date',
                 function ($attribute, $value, $fail) use ($request) {
                     $start = Carbon::parse($request->start_date);
                     $end = Carbon::parse($value);
                     if ($end->diffInDays($start) > 365) {
                         $fail('Thời hạn voucher không được vượt quá 1 năm.');
                     }
                 }
             ],
             'usage_limit' => [
                 'required',
                 'integer',
                 'min:1',
                 function ($attribute, $value, $fail) use ($voucher) {
                     if ($value < $voucher->used_count) {
                         $fail('Giới hạn sử dụng không thể nhỏ hơn số lần đã sử dụng ('.$voucher->used_count.').');
                     }
                 }
             ],
             'voucher_active' => 'required|boolean'
         ];
     
         // Thêm rules cho discount_value và max_discount dựa trên discount_type
         if ($request->discount_type === 'percent') {
             $rules['discount_value'] = 'required|numeric|between:0,100';
             $rules['max_discount'] = [
                 'required',
                 'numeric',
                 'min:0',
                 'lte:minimum_order_value'
             ];
         } else {
             $rules['discount_value'] = [
                 'required',
                 'numeric',
                 'min:1000',
                 'lte:minimum_order_value'
             ];
             $rules['max_discount'] = [
                 'nullable',
                 'numeric',
                 'min:0',
                 'lte:minimum_order_value'
             ];
         }
     
         $validator = Validator::make($request->all(), $rules);
     
         if ($validator->fails()) {
             return response()->json([
                 'message' => 'Dữ liệu không hợp lệ',
                 'errors' => $validator->errors()
             ], 422);
         }
     
         // Kiểm tra logic bổ sung
         if ($request->discount_type === 'fixed') {
             if ($request->discount_value > $request->minimum_order_value) {
                 return response()->json([
                     'message' => 'Dữ liệu không hợp lệ',
                     'errors' => [
                         'discount_value' => ['Giá trị giảm không được lớn hơn giá trị đơn hàng tối thiểu']
                     ]
                 ], 422);
             }
         }
     
         DB::beginTransaction();
         try {
             // Cập nhật voucher
             $voucher->update([
                 'name' => trim($request->name),
                 'minimum_order_value' => $request->minimum_order_value,
                 'discount_type' => $request->discount_type,
                 'discount_value' => $request->discount_value,
                 'max_discount' => $request->max_discount,
                 'start_date' => $request->start_date,
                 'end_date' => $request->end_date,
                 'usage_limit' => $request->usage_limit,
                 'voucher_active' => $request->voucher_active
             ]);
     
             DB::commit();
             return response()->json([
                 'success' => true,
                 'message' => 'Cập nhật voucher thành công',
                 'data' => $voucher
             ]);
         } catch (Exception $e) {
             DB::rollBack();
             Log::error('Lỗi cập nhật voucher: ' . $e->getMessage());
             return response()->json([
                 'success' => false,
                 'message' => 'Đã xảy ra lỗi khi cập nhật voucher',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
    /**
     * @OA\Delete(
     *     path="/api/vouchers/{id}",
     *     summary="Xóa voucher",
     *     description="Xóa voucher khỏi hệ thống bằng ID. Yêu cầu quyền admin.",
     *     tags={"Voucher"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của voucher cần xóa",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Voucher đã được xóa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voucher không tìm thấy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Không tìm thấy voucher.")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Tìm voucher theo ID
            $voucher = Voucher::findOrFail($id);

            // Kiểm tra xem voucher có đang được sử dụng không
            // if ($voucher->used_count > 0) {
            //     return response()->json([
            //         'message' => 'Không thể xóa voucher đã được sử dụng'
            //     ], 400);
            // }

            // Thực hiện xóa voucher
            $voucher->delete();

            DB::commit();
            return response()->json([
                'message' => 'Xóa voucher thành công'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xóa voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Hàm phụ trợ để xác định trạng thái voucher
    private function getVoucherStatus(Voucher $voucher, Carbon $now): string
    {
        if (!$voucher->voucher_active) {
            return 'Không hoạt động';
        }
        if ($now < $voucher->start_date) {
            return 'Chưa bắt đầu';
        }
        if ($now > $voucher->end_date) {
            return 'Đã hết hạn';
        }
        if ($voucher->used_count >= $voucher->usage_limit) {
            return 'Đã hết lượt sử dụng';
        }
        return 'Đang hoạt động';
    }



    // public function apply(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'code' => 'required|string',
    //         'order_total' => 'required|numeric|min:0',
    //         'products' => 'required|array|min:1',
    //         'products.*.id' => 'required|integer|exists:products,id',
    //         'products.*.price' => 'required|numeric|min:0',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.category_id' => 'required|integer|exists:categories,id'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     try {
    //         $voucher = Voucher::where('code', $request->code)->first();
    //         if (!$voucher) {
    //             return response()->json(['error' => 'Mã voucher không hợp lệ'], 404);
    //         }

    //         // Validate trạng thái voucher
    //         $now = Carbon::now();
    //         if (!$voucher->voucher_active ||
    //         $now < $voucher->start_date ||
    //         $now > $voucher->end_date ||
    //         $voucher->used_count >= $voucher->usage_limit) {
    //         return response()->json(['error' => 'Voucher không còn hiệu lực'], 400);
    //     }

    //         // Calculate applicable total
    //         $products = collect($request->products);
    //         $applicableProducts = $products->filter(function ($product) use ($voucher) {
    //             $applicableIds = json_decode($voucher->applicable_ids);
    //             // Kiểm tra voucher áp dụng cho sản phẩm hay danh mục
    //             return $voucher->applicable_type === 'product'
    //                 ? in_array($product['id'], $applicableIds)
    //                 : in_array($product['category_id'], $applicableIds);
    //         });

    //         $applicableTotal = $applicableProducts->sum(function ($product) {
    //             return $product['price'] * $product['quantity'];
    //         });

    //         if ($applicableTotal < $voucher->minimum_order_value) {
    //             return response()->json([
    //                 'error' => 'Tổng đơn hàng không đạt giá trị tối thiểu',
    //                 'minimum_required' => $voucher->minimum_order_value,
    //                 'applicable_total' => $applicableTotal
    //             ], 400);
    //         }

    //         // Calculate discount
    //         $discount = $this->calculateDiscount($voucher, $applicableTotal);

    //         return response()->json([
    //             'discount_amount' => $discount, // Số tiền giảm giá
    //             'applicable_products' => $applicableProducts->pluck('id'), // Danh sách ID sản phẩm được áp dụng
    //             'applicable_total' => $applicableTotal, // Tổng tiền được áp dụng
    //             'voucher_details' => $voucher // Chi tiết voucher
    //         ]);

    //     } catch (Exception $e) {
    //     return response()->json([
    //         'message' => 'Lỗi khi áp dụng voucher',
    //         'error' => $e->getMessage()
    //     ], 500);
    // }
    // }

    // private function calculateDiscount(Voucher $voucher, float $total): float
    // {
    //     $discount = $voucher->discount_type === 'percent'
    //         ? $total * ($voucher->discount_value / 100)
    //         : $voucher->discount_value;

    //     // Apply max discount if set and applicable
    //     if ($voucher->discount_type === 'percent' &&
    //         $voucher->max_discount &&
    //         $discount > $voucher->max_discount) {
    //         $discount = $voucher->max_discount;
    //     }

    //     return round($discount, 2);
    // }
    public function getAllVouchers(Request $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }
    
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.',
            ], 403);
        }
    
        try {
            $query = Voucher::query();
            $now = Carbon::now();
    
            // Lấy trạng thái voucher từ request (active/inactive/all)
            $status = $request->get('status', 'all');
    
         
    
            switch ($status) {
                case 'active':
                    $query->where('voucher_active', true)
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now)
                        ->where(function($q) {
                            $q->where('usage_limit', '>', DB::raw('used_count'))
                              ->orWhereNull('usage_limit');
                        });
                    break;
    
                case 'inactive':
                    $query->where(function ($q) use ($now) {
                        $q->where('voucher_active', false)
                            ->orWhere('start_date', '>', $now)
                            ->orWhere('end_date', '<', $now)
                            ->orWhere('used_count', '>=', DB::raw('usage_limit'));
                    });
                    break;
    
                case 'all':
                default:
                    // Không thêm điều kiện gì cả
                    break;
            }
    
         
            $vouchers = $query->get();
    
            return response()->json([
                'success' => true,
                'vouchers' => $vouchers,
                'summary' => [
                    'total_vouchers' => $vouchers->count(),
                    'active_vouchers' => $vouchers->where('voucher_active', true)
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now)
                        ->where('used_count', '<', DB::raw('usage_limit'))
                        ->count(),
                    'inactive_vouchers' => $vouchers->where(function($voucher) use ($now) {
                        return !$voucher->voucher_active 
                            || $voucher->start_date > $now
                            || $voucher->end_date < $now
                            || $voucher->used_count >= $voucher->usage_limit;
                    })->count(),
                ],
                'message' => 'Lấy danh sách voucher thành công'
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách voucher:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchVouchers(Request $request)
    {
       $currentUser = auth('api')->user();
       if (!$currentUser || !$currentUser->isAdmin()) {
           return response()->json([
               'success' => false,
               'message' => 'Bạn không phải admin.' 
           ], 403);
       }
    
       try {
           $keyword = $request->input('keyword');
           $status = $request->input('status');
    
           $query = Voucher::query();
    
           if ($keyword) {
               $query->where(function($q) use ($keyword) {
                   $q->where('name', 'LIKE', "%{$keyword}%")
                     ->orWhere('code', 'LIKE', "%{$keyword}%");
               });
           }
    
           if ($status !== null) {
               $query->where('voucher_active', $status);
           }
    
           $vouchers = $query->orderBy('created_at', 'desc')->get();
    
           $activeVouchers = $vouchers->filter(function($voucher) {
               return $voucher->voucher_active && 
                      $voucher->end_date >= now() &&
                      $voucher->used_count < $voucher->usage_limit;
           });
    
           $inactiveVouchers = $vouchers->filter(function($voucher) {
               return !$voucher->voucher_active || 
                      $voucher->end_date < now() ||
                      $voucher->used_count >= $voucher->usage_limit;
           });
    
           return response()->json([
               'success' => true,
               'message' => 'Tìm kiếm voucher thành công',
               'data' => [
                   'active_vouchers' => $activeVouchers->values(),
                   'inactive_vouchers' => $inactiveVouchers->values(),
                   'total_active' => $activeVouchers->count(),
                   'total_inactive' => $inactiveVouchers->count()
               ]
           ], 200);
    
       } catch (QueryException $e) {
        
           return response()->json([
               'success' => false,
               'message' => 'Lỗi truy vấn cơ sở dữ liệu',
               'error' => [
                   'code' => $e->getCode(),
                   'message' => $e->getMessage(),
                   'sql_state' => $e->getSqlState()
               ]
           ], 500);
       } catch (ModelNotFoundException $e) {
         
           return response()->json([
               'success' => false,
               'message' => 'Không tìm thấy mẫu dữ liệu',
               'error' => [
                   'code' => $e->getCode(),
                   'message' => $e->getMessage()
               ]
           ], 404);
       } catch (ValidationException $e) {
         
           return response()->json([
               'success' => false,
               'message' => 'Lỗi xác thực dữ liệu',
               'errors' => $e->errors()
           ], 422);
       } catch (Exception $e) {
          
           return response()->json([
               'success' => false,
               'message' => 'Tìm kiếm voucher thất bại',
               'error' => [
                   'code' => $e->getCode(),
                   'message' => $e->getMessage(),
                   'trace' => config('app.debug') ? $e->getTraceAsString() : null
               ]
           ], 500);
       }
    }
}
