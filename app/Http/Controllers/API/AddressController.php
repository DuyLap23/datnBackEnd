<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequests;
use App\Models\Address;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/addresses",
     *     summary="Lấy danh sách địa chỉ",
     *     tags={"Address"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách địa chỉ thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách địa chỉ thành công."),
     *             @OA\Property(property="addresses", type="array", @OA\Items(ref="#/components/schemas/Address"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Bạn cần đăng nhập để xem danh sách địa chỉ."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy địa chỉ nào."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Yêu cầu không thể xử lý do đầu vào không hợp lệ."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Đã xảy ra lỗi không mong muốn."
     *     )
     * )
     */
    public function index()
    {
        try {
            $currentUser = auth('api')->user();

            // Kiểm tra nếu không có người dùng đang đăng nhập
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem danh sách địa chỉ.'
                ], 401); // 401 Unauthorized
            }

            // Nếu người dùng là admin, hiển thị tất cả địa chỉ
            if ($currentUser->isAdmin()) {
                $addresses = Address::all();
            } else {
                // Nếu không phải admin, chỉ hiển thị địa chỉ của người dùng hiện tại
                $addresses = Address::where('user_id', $currentUser->id)->latest('id')->get();
            }

            // Nếu không tìm thấy địa chỉ nào
            if ($addresses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy địa chỉ nào.',
                ], 404);
            }

            return response()->json([
                'message' => 'Lấy danh sách địa chỉ thành công.',
                'success' => true,
                'addresses' => $addresses,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Nếu không tìm thấy địa chỉ, trả về lỗi 404
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy được địa chỉ.'
            ], 404);
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::info('Đã xảy ra lỗi: ' . $e->getMessage());

            // Nếu có lỗi không mong muốn khác, trả về lỗi 500
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.'
            ], 500);
        }
    }





    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/addresses",
     *     summary="Thêm địa chỉ mới",
     *     tags={"Address"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_name", "phone_number", "city", "district", "ward", "detail_address"},
     *             @OA\Property(property="address_name", type="string", example="Nhà riêng"),
     *             @OA\Property(property="phone_number", type="string", example="0123456789"),
     *             @OA\Property(property="city", type="string", example="Hà Nội"),
     *             @OA\Property(property="district", type="string", example="Hoàn Kiếm"),
     *             @OA\Property(property="ward", type="string", example="Tràng Tiền"),
     *             @OA\Property(property="detail_address", type="string", example="Số 1, phố Tràng Tiền"),
     *             @OA\Property(property="is_default", type="boolean", example=true, description="Đặt địa chỉ này làm mặc định")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Thêm địa chỉ thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Thêm địa chỉ thành công."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Bạn cần đăng nhập để thêm địa chỉ."
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Địa chỉ này đã tồn tại."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Thêm địa chỉ thất bại."
     *     )
     * )
     */
    public function store(AddressRequests $request)
    {
        // Lấy người dùng hiện tại từ token Bearer
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thêm địa chỉ.'
            ], 401); // 401 Unauthorized
        }
        DB::beginTransaction();
        try {
            // Kiểm tra xem địa chỉ đã tồn tại chưa
            $existingAddress = Address::where([
                ['user_id', '=', $currentUser->id],
                ['ward', '=', $request->ward],
                ['detail_address', '=', $request->detail_address],
            ])->first();

            if ($existingAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Địa chỉ này đã tồn tại.',
                ], 409); // 409 Conflict
            }

            // Thêm mới địa chỉ
            $newAddress = Address::create([
                'user_id' => $currentUser->id, // Gán user_id của người dùng hiện tại
                'address_name' => $request->address_name,
                'phone_number' => $request->phone_number,
                'city' => $request->city,
                'district' => $request->district,
                'ward' => $request->ward,
                'detail_address' => $request->detail_address,
                'is_default' => true,
            ]);
            // Cập nhật các địa chỉ khác thành không mặc định
            Address::where('user_id', $currentUser->id)
                    ->where('id', '!=', $newAddress->id)
                    ->update(['is_default' => false]);


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thêm địa chỉ thành công.',
                'data' => $newAddress, // Trả về địa chỉ mới được thêm
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Thêm địa chỉ thất bại.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/addresses/{id}",
     *     tags={"Address"},
     *     summary="Lấy thông tin địa chỉ",
     *     description="Lấy thông tin chi tiết của một địa chỉ dựa trên ID. Cần đăng nhập để truy cập.",
     *     operationId="getAddressById",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của địa chỉ cần lấy thông tin.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thông tin thành công"),
     *             @OA\Property(property="address", type="object", ref="#/components/schemas/Address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Bạn cần đăng nhập để xem thông tin.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Bạn không có quyền xem thông tin của người dùng khác.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền xem thông tin của người dùng khác.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tồn tại địa chỉ này.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tồn tại địa chỉ này.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Đã xảy ra lỗi không mong muốn.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi không mong muốn.")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            // Kiểm tra nếu người dùng chưa đăng nhập
            $currentUser = auth('api')->user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem thông tin.'
                ], 401); // 401 Unauthorized
            }

            // Lấy thông tin địa chỉ dựa trên ID
            $address = Address::findOrFail($id);

            // Kiểm tra nếu địa chỉ không thuộc về người dùng hiện tại và người dùng không phải là admin
            if ($address->user_id != $currentUser->id && !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem thông tin của người dùng khác.'
                ], 403); // 403 Forbidden
            }

            // Trả về phản hồi JSON chứa thông tin địa chỉ
            return response()->json([
                'message' => 'Lấy thông tin thành công',
                'success' => true,
                'address' => $address,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Nếu không tìm thấy địa chỉ, trả về lỗi 404
            return response()->json([
                'success' => false,
                'message' => 'Không tồn tại địa chỉ này.'
            ], 404);
        } catch (\Exception $e) {
            // Nếu có lỗi không mong muốn khác, trả về lỗi 500
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.'
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/user/addresses/{id}",
     *     summary="Cập nhật địa chỉ",
     *     tags={"Address"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của địa chỉ cần cập nhật",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"address_name", "phone_number", "city", "district", "ward", "detail_address"},
     *             @OA\Property(property="address_name", type="string", example="123 Đường ABC"),
     *             @OA\Property(property="phone_number", type="string", example="0123456789"),
     *             @OA\Property(property="city", type="string", example="Thành phố HCM"),
     *             @OA\Property(property="district", type="string", example="Quận 1"),
     *             @OA\Property(property="ward", type="string", example="Phường B"),
     *             @OA\Property(property="detail_address", type="string", example="Số 123, khu phố 1"),
     *             @OA\Property(property="is_default", type="boolean", example=true),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật địa chỉ thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thành công."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="addresses", type="array",
     *                     @OA\Items(ref="#/components/schemas/Address")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Không được phép truy cập",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền chỉnh sửa địa chỉ của người dùng khác",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền chỉnh sửa địa chỉ của người dùng khác.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy địa chỉ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy địa chỉ.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="address_name", type="array",
     *                     @OA\Items(type="string", example="Trường này là bắt buộc.")
     *                 )
     *                 // Thêm các trường lỗi khác nếu cần
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi không xác định",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thất bại."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */

    public function update(AddressRequests $request, string $id)
    {
        // Lấy người dùng hiện tại từ token Bearer
        $currentUser = auth('api')->user();

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xem thông tin.'
            ], 401); // 401 Unauthorized
        }

        // Kiểm tra xem người dùng hiện tại có phải là chủ sở hữu của địa chỉ không
        $address = Address::findOrFail($id);

        if ($address->user_id != $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa địa chỉ của người dùng khác.'
            ], 403); // 403 Forbidden
        }

        DB::beginTransaction();
        try {
            // Kiểm tra nếu `is_default` là true, cập nhật các địa chỉ khác thành không mặc định
            if ($request->has('is_default') && $request->is_default) {
                Address::where('user_id', $currentUser->id)->update(['is_default' => false]);
            }

            // Cập nhật địa chỉ
            $address->update([
                'address_name' => $request->address_name,
                'phone_number' => $request->phone_number,
                'city' => $request->city,
                'district' => $request->district,
                'ward' => $request->ward,
                'detail_address' => $request->detail_address,
                'is_default' => $request->is_default,
            ]);

            // Load danh sách địa chỉ mới nhất
            $currentUser->load(['addresses' => function ($query) {
                $query->latest('id');
            }]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công.',
                'data' => [
                    'addresses' => $currentUser->addresses, // Trả về danh sách địa chỉ
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy địa chỉ.',
            ], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thông tin thất bại.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/addresses/{id}",
     *     summary="Xóa địa chỉ của người dùng",
     *     tags={"Address"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của địa chỉ cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Xóa địa chỉ thành công"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không thể xóa địa chỉ mặc định"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tồn tại hoặc địa chỉ không tồn tại"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra"
     *     ),
     * )
     */
    public function destroy(string $id)
    {
        try {
            // Lấy thông tin người dùng hiện tại
            $user = auth('api')->user();
            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return response()->json(['message' => 'Người dùng không tồn tại!'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // Tìm địa chỉ theo ID và kiểm tra thuộc về người dùng
            $address = Address::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            // Kiểm tra nếu địa chỉ không tồn tại
            if (!$address) {
                return response()->json(['message' => 'Địa chỉ không tồn tại!'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // Kiểm tra nếu địa chỉ là mặc định
            if ($address->is_default) {
                return response()->json(['message' => 'Không thể xoá địa chỉ mặc định'], 403, [], JSON_UNESCAPED_UNICODE);
            }

            // Xóa địa chỉ
            $address->delete();

            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['message' => 'Có lỗi xảy ra', 'error' => $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

}
