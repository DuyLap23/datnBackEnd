<?php

namespace App\Http\Controllers\API;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BrandRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
  /**
     * @OA\Get(
     *     path="/api/admin/brands",
     *     summary="Lấy danh sách danh mục",
     *     description="Trả về danh sách thương hiệu.",
     *     tags={"Brand"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Số trang hiện tại",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy thành công thương hiệu",
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="brand",
     *                     type="object",
     *                     @OA\Property(
     *                         property="current_page",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="id",
     *                                 type="integer",
     *                                 example=1,
     *                             ),
     *                             @OA\Property(
     *                                 property="name",
     *                                 type="string",
     *                                 example="Thương hiệu",
     *                             ),
     *                             @OA\Property(
     *                                 property="image",
     *                                 type="string",
     *                                 example="image.png",
     *                             ),
     *                           
     *                            
     *                                  
     *                                     ),
     *                                 )
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="total",
     *                         type="integer",
     *                         example=10
     *                     ),
     *                     @OA\Property(
     *                         property="last_page",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="per_page",
     *                         type="integer",
     *                         example=10
     *                     ),
     *                     @OA\Property(
     *                         property="from",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="to",
     *                         type="integer",
     *                         example=10
     *                     )
     *                 ),
     *                
     *                    
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lỗi khi lấy danh mục.",
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Server Error Message",
     *             ),
     *         )
     *     )
     * )
     */
    private const PATH_UPLOAD = 'brands';
    public function index()
    {
        $brands = Brand::all();
        return response()->json(
            [
                'message' => 'Lấy thành công danh sách thương hiệu',
                'data' => $brands,
            ],
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     * path="/api/admin/brands/",
     * summary="Thêm thương hiệu",
     * description="Thêm thương hiệu vào hệ thống.",
     * tags={"Brand"},
     * security={{"Bearer": {}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Tên thương hiệu",
     * example="Thương hiệu 1",
     * ),
     * @OA\Property(
     * property="image",
     * type="string",
     * description="Hình ảnh thương hiệu",
     * example="image.png",
     * ),
   
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=201,
     * description="Thành công",
     * @OA\JsonContent(
     * @OA\Property(
     * property="success",
     * type="boolean",
     * example=true,
     * ),
     * @OA\Property(
     * property="message",
     * type="string",
     * example="Thêm thương hiệu thành công.",
     * ),
     * @OA\Property(
     * property="data",
     * type="object",
     * @OA\Property(
     * property="brand",
     * type="object",
     * @OA\Property(
     * property="id",
     * type="bigint",
     * example=1,
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * example="Thương hiệu 1",
     * ),
     * @OA\Property(
     * property="image_url",
     * type="string",
     * example="https://apitopdeal.shop/storage/brand/image.png",
     * ),
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=500,
     * description="Thêm thương hiệu thất bại",
     * @OA\JsonContent(
     * @OA\Property(
     * property="success",
     * type="boolean",
     * example=false,
     * ),
     * @OA\Property(
     * property="message",
     * type="string",
     * example="Thêm thương hiệu thất bại",
     * ),
     * @OA\Property(
     * property="error",
     * type="string",
     * example="Error details...",
     * ),
     * ),
     * ),
     * )
     */
    public function store(BrandRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => [ 'max:255'],
                'image' => [ 'nullable','mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'description' => [ 'nullable', 'max:255'],
            ]);

            // Xử lý hình ảnh nếu có
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store(self::PATH_UPLOAD, 'public');
            }

            // Tạo mới thương hiệu
            Brand::query()->create($data);
            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Thêm thương hiệu thành công.',
                ],
                201
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Thêm thương hiệu thất bại',
                    'error' => $exception->getMessage(),
                ],
                500
            );
        }
    }


    /**
     * Display the specified resource.
     */
     /**
     * @OA\Get(
     *     path="/api/admin/brand/{id}",
     *     summary="Lấy chi tiết thương hiệu",
     *     tags={"Brand"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của thương hiệu",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công dữ liệu của thương hiệu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của bản ghi {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="brand Name"),
     *                 @OA\Property(property="image", type="string", example="image.png"),
     *                 @OA\Property(property="description", type="string", example=""),
     *                  @OA\Property(property="	deleted_at", type="string", example=""),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Thương hiệu không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu không thành công"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Brand]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu không thành công"),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json(
                [
                    'message' => 'Lấy thành công dữ liệu của thương hiệu ' . $id,
                    'data' => $brand,
                ],
                200,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'message' => 'Lấy dữ liệu không thành công',
                    'error' => $exception->getMessage()
                ],
                500,
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
     /**
     * @OA\Put(
     *     path="/api/admin/brand/{id}",
     *     summary="Cập nhật thương hiệu",
     *     tags={"Brands"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của danh mục",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Category Name"),
     *             @OA\Property(property="image", type="string", format="binary", example="image.jpg"),
     *             @OA\Property(property="parent_id", type="string", example="1"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của bản ghi {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="brand Name"),
     *                 @OA\Property(property="image", type="string", example="image.png"),
     *                 @OA\Property(property="description", type="string", example=""),
     *                  @OA\Property(property="	deleted_at", type="string", example=""),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-19T12:34:56Z"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Danh mục không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thất bại"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Category]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thất bại"),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'description' => ['nullable', 'max:255'],
            ]);

            $brand = Brand::query()->findOrFail($id);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store(self::PATH_UPLOAD, 'public');

                $image_old = $brand->image;
            } else {
                $image_old = null;
            }

            $brand->update($data);

            if ($image_old && Storage::exists($image_old)) {
                Storage::delete($image_old);
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Cập nhật thương hiệu thành công.',
                    'data' => $brand,
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Cập nhật thương hiệu thất bại',
                    'error' => $exception->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
        /**
     * @OA\Delete(
     *     path="api/admin/brands/{id}",
     *     summary="Xóa danh mục",
     *     tags={"brands"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của thương hiệu cần xóa",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thương hiệu thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa thương hiệu thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi xóa thương hiệu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xóa thương hiệu không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $brand->delete();

            if ($brand->image && Storage::exists($brand->image)) {
                Storage::delete($brand->image);
            }
              // Ghi log thành công (nếu cần)
              Log::info("Thương hiệu với ID {$id} đã được xóa.");

            return response()->json(
                [
                    'message' => 'Xoá thương hiệu thành công.',
                ],
                200,
            );
        } catch (\Exception $e) {
           // Ghi log lỗi
           Log::error("Lỗi khi xóa thương hiệu ID {$id}: " . $e->getMessage());

           return response()->json(
               [
                   'success' => false,
                   'message' => 'Xóa thương hiệu không thành công.',
                   'error' => $e->getMessage(),
               ],
               500,
            );
        }
    }
   
}
