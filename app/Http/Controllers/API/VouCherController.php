<?php

namespace App\Http\Controllers\Api;


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
use Illuminate\Support\Facades\Validator;



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

            // Join với bảng products để lấy thông tin category
            $cartItems = Cart::where('user_id', $user->id)
                ->join('products', 'carts.product_id', '=', 'products.id')
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

            // Tổng giá trị giỏ hàng và danh sách sản phẩm
            $cartProducts = [];
            $cartTotal = 0;
            $categoryIds = [];


            foreach ($cartItems as $item) {
                $price = $item->product->price_sale ?? $item->product->price_regular;
                $cartTotal += $price * $item->quantity;

                $cartProducts[] = [
                    'product_id' => $item->product->id,
                    'name' => $item->product->name,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'category_id' => $item->product->category_id,
                ];

                $categoryIds[] = $item->product->category_id;
            }

            // Loại bỏ trùng lặp
            $categoryIds = array_unique($categoryIds);


            // Query voucher hoạt động
            $now = Carbon::now();
            $vouchers = Voucher::where('voucher_active', true)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('usage_limit', '>', DB::raw('used_count'))
                ->where('minimum_order_value', '<=', $cartTotal)
                ->get();

                $applicableVouchers = $vouchers->map(function ($voucher) use ($cartProducts, $cartTotal, $categoryIds) {
                    $applicable = false;
                    $applicableProducts = [];
                
                    switch ($voucher->applicable_type) {
                        case 'category':
                            // Lấy danh sách category_ids từ voucher
                            $voucherCategories = json_decode($voucher->applicable_ids, true) ?? [];
                            
                            // Check xem trong giỏ hàng có sản phẩm nào thuộc các category của voucher không
                            $applicable = count(array_intersect($categoryIds, $voucherCategories)) > 0;
                            
                            if ($applicable) {
                                // Lọc sản phẩm thuộc category được áp dụng
                                $applicableProducts = array_values(array_filter($cartProducts, function ($product) use ($voucherCategories) {
                                    return in_array($product['category_id'], $voucherCategories);
                                }));
                            }
                            break;
                
                        case 'product':
                            // Voucher áp dụng theo sản phẩm cụ thể
                            $voucherProducts = json_decode($voucher->applicable_ids, true) ?? [];
                            $cartProductIds = array_column($cartProducts, 'product_id');
                            
                            // Kiểm tra xem có sản phẩm nào trong giỏ hàng phù hợp với voucher không
                            $applicable = count(array_intersect($cartProductIds, $voucherProducts)) > 0;
                            
                            if ($applicable) {
                                $applicableProducts = array_values(array_filter($cartProducts, function ($product) use ($voucherProducts) {
                                    return in_array($product['product_id'], $voucherProducts);
                                }));
                            }
                            break;
                    }
                
                    if (!$applicable || empty($applicableProducts)) {
                        return null;
                    }
                
                    // Tính tổng giá trị các sản phẩm được áp dụng
                    $applicableTotal = array_reduce($applicableProducts, function ($carry, $product) {
                        return $carry + ($product['price'] * $product['quantity']);
                    }, 0);
                
                    // Tính giá trị giảm giá
                    $discountAmount = 0;
                    if ($voucher->discount_type === 'percentage') {
                        $discountAmount = ($applicableTotal * $voucher->discount_value) / 100;
                        if ($voucher->max_discount > 0) {
                            $discountAmount = min($discountAmount, $voucher->max_discount);
                        }
                    } else {
                        $discountAmount = min($voucher->discount_value, $applicableTotal);
                    }
                
                    return [
                        'id' => $voucher->id,
                        'code' => $voucher->code,
                        'discount_type' => $voucher->discount_type,
                        'discount_value' => $voucher->discount_value,
                        'max_discount' => $voucher->max_discount,
                        'potential_discount' => $discountAmount,
                        'applicable_products' => $applicableProducts,
                        'minimum_order_value' => $voucher->minimum_order_value
                    ];
                })->filter()->values();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách voucher thành công',
                'data' => [
                    'cart_total' => $cartTotal,
                    'vouchers' => $applicableVouchers->values(),
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
        // Validation rules cơ bản
        $rules = [
            'name' => 'required|string|max:255',
            'minimum_order_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percent',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'required|integer|min:1',
            'voucher_active' => 'required|boolean',
            'applicable_type' => 'required|string|in:product,category',
            'applicable_ids' => 'required|array|min:1',
            'applicable_ids.*' => 'required|integer|min:1',
        ];

        // Thêm validation rule cho discount_value tùy theo discount_type
        if ($request->discount_type === 'percent') {
            $rules['discount_value'] = 'required|numeric|min:0|max:100';
            $rules['max_discount'] = 'required|numeric|min:0'; // Bắt buộc có max_discount cho percent
        } else { // fixed
            $rules['discount_value'] = 'required|numeric|min:1000';
            $rules['max_discount'] = 'nullable|numeric|min:0'; // Cho phép null với fixed nhưng nếu có thì phải >= 0
        }

        $validator = Validator::make($request->all(), $rules, [
            'discount_type.in' => 'Loại giảm giá phải là một trong các giá trị: fixed hoặc percent',
            'discount_value.max' => 'Giá trị giảm giá theo phần trăm không được vượt quá 100%',
            'discount_value.min' => 'Giá trị giảm giá phải lớn hơn 0',
            'max_discount.required' => 'Vui lòng nhập giá trị giảm giá tối đa cho voucher phần trăm',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
                'valid_values' => [
                    'discount_type' => ['fixed', 'percent'],
                    'discount_value' => [
                        'percent' => 'từ 0 đến 100',
                        'fixed' => 'lớn hơn hoặc bằng 1000'
                    ]
                ]
            ], 422);
        }

        // Kiểm tra logic bổ sung cho giá trị giảm giá
        // Thêm vào sau validation rules cơ bản
        if ($request->discount_type === 'fixed') {
            // Kiểm tra giá trị giảm không được lớn hơn giá trị đơn hàng tối thiểu
            if ($request->discount_value > $request->minimum_order_value) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => [
                        'discount_value' => ['Giá trị giảm giá không được lớn hơn giá trị đơn hàng tối thiểu']
                    ]
                ], 422);
            }

            // Kiểm tra max_discount nếu được cung cấp
            if ($request->filled('max_discount') && $request->max_discount < $request->discount_value) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => [
                        'max_discount' => ['Giá trị giảm tối đa không thể nhỏ hơn giá trị giảm']
                    ]
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $applicable_ids = $request->applicable_ids;

            if ($request->applicable_type === 'category') {
                // Kiểm tra xem các category có tồn tại không
                $existingCategories = Category::whereIn('id', $request->applicable_ids)
                   
                    ->pluck('id')
                    ->toArray();
            
                if (count($existingCategories) !== count($request->applicable_ids)) {
                    return response()->json([
                        'message' => 'Dữ liệu không hợp lệ',
                        'error' => 'Một số danh mục không tồn tại hoặc không còn active'
                    ], 400);
                }
            
                // Lưu trực tiếp category_ids
                $applicable_ids = $request->applicable_ids;
            } else {
                // Kiểm tra sản phẩm tồn tại và đang active
                $existingProducts = Product::whereIn('id', $request->applicable_ids)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();
            
                if (count($existingProducts) !== count($request->applicable_ids)) {
                    return response()->json([
                        'message' => 'Dữ liệu không hợp lệ',
                        'error' => 'Một số sản phẩm không tồn tại hoặc không còn active'
                    ], 400);
                }
            
                $applicable_ids = $request->applicable_ids;
            }

            // Tạo mã voucher
            $code = strtoupper(Str::random(10));
            while (Voucher::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(10));
            }
            if ($request->applicable_type === 'category') {
                // Lưu trực tiếp category_ids
                $applicable_ids = $request->applicable_ids;
            } else {
                // Giữ nguyên logic cũ cho product
                $applicable_ids = $request->applicable_ids;
            }

            // Chuẩn bị dữ liệu voucher
            $voucherData = [

                'name' => $request->name,
                'minimum_order_value' => $request->minimum_order_value,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'usage_limit' => $request->usage_limit,
                'voucher_active' => $request->voucher_active,
                'applicable_type' => $request->applicable_type,
                'applicable_ids' => json_encode($applicable_ids),
                'code' => $code,
            ];

            // Thêm max_discount nếu là voucher phần trăm
            if ($request->discount_type === 'percent') {
                $voucherData['max_discount'] = $request->max_discount;
            }

            // Tạo voucher mới
            $voucher = Voucher::create($voucherData);

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
            // Tìm voucher theo ID
            $voucher = Voucher::findOrFail($id);

            // Kiểm tra xem voucher có tồn tại không
            if (!$voucher) {
                return response()->json([
                    'message' => 'Không tìm thấy voucher'
                ], 404);
            }

            // Lấy thêm thông tin về sản phẩm hoặc danh mục áp dụng
            $applicableIds = json_decode($voucher->applicable_ids);
            if ($voucher->applicable_type === 'product') {
                $voucher->applicable_items = Product::whereIn('id', $applicableIds)->get();
            } else {
                $voucher->applicable_items = Category::whereIn('id', $applicableIds)->get();
            }

            // Thêm thông tin về trạng thái hiện tại của voucher
            $now = Carbon::now();
            $voucher->current_status = $this->getVoucherStatus($voucher, $now);

            return response()->json($voucher);
        } catch (Exception $e) {
            return response()->json([
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
        // Tìm voucher
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return response()->json([
                'message' => 'Không tìm thấy voucher'
            ], 404);
        }

        // Validation dữ liệu
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'minimum_order_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'usage_limit' => 'required|integer|min:1',
            'voucher_active' => 'required|boolean',
            'applicable_type' => 'required|string',
            'applicable_ids' => 'required|array|min:1',
            'applicable_ids.*' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra applicable_type
        if (!in_array($request->applicable_type, ['product', 'category'])) {
            return response()->json([
                'message' => 'Loại áp dụng không hợp lệ',
                'error' => 'Loại áp dụng phải là "product" hoặc "category"'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Kiểm tra ID sản phẩm/danh mục
            $applicable_ids = is_array($request->applicable_ids) ? $request->applicable_ids : [];

            if ($request->applicable_type === 'product') {
                $existingCount = Product::whereIn('id', $applicable_ids)->count();
                if ($existingCount !== count($applicable_ids)) {
                    return response()->json([
                        'message' => 'Dữ liệu không hợp lệ',
                        'error' => 'Một số sản phẩm không tồn tại trong hệ thống'
                    ], 400);
                }
            } else {
                $existingCount = Category::whereIn('id', $applicable_ids)->count();
                if ($existingCount !== count($applicable_ids)) {
                    return response()->json([
                        'message' => 'Dữ liệu không hợp lệ',
                        'error' => 'Một số danh mục không tồn tại trong hệ thống'
                    ], 400);
                }
            }

            // Cập nhật voucher
            $voucher->update([
                'name' => $request->name,
                'minimum_order_value' => $request->minimum_order_value,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'usage_limit' => $request->usage_limit,
                'voucher_active' => $request->voucher_active,
                'applicable_type' => $request->applicable_type,
                'applicable_ids' => json_encode($applicable_ids)
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Cập nhật voucher thành công',
                'data' => $voucher
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
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
            if ($voucher->used_count > 0) {
                return response()->json([
                    'message' => 'Không thể xóa voucher đã được sử dụng'
                ], 400);
            }

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
                        ->where('used_count', '<', DB::raw('usage_limit'));
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
                    break;
            }

            $vouchers = $query->get();

            // Tính toán summary trực tiếp từ collection
            $summary = [
                'total_vouchers' => $vouchers->count(),
                'active_vouchers' => $vouchers->where('status', 'active')->count(),
                'inactive_vouchers' => $vouchers->where('status', 'inactive')->count(),
            ];

            return response()->json([
                'vouchers' => $vouchers,
                'summary' => $summary,
                'message' => 'Lấy danh sách voucher thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
