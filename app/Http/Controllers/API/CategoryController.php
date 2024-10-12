<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    const PATH_UPLOAD = 'categories';

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Lấy danh sách danh mục",
     *     description="Trả về danh sách tất cả các danh mục chính và danh mục con.",
     *     tags={"Category"},
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
     *                 example="Lấy thành công danh mục",
     *             ),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=3,
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Category 2",
     *                     ),
     *                     @OA\Property(
     *                         property="slug",
     *                         type="string",
     *                         example="",
     *                     ),
     *                     @OA\Property(
     *                         property="image",
     *                         type="string",
     *                         example="https://picsum.photos/200/300?random=2",
     *                     ),
     *                     @OA\Property(
     *                         property="parent_id",
     *                         type="integer",
     *                         nullable=true,
     *                         example=null,
     *                     ),
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2024-09-05T16:26:27.000000Z",
     *                     ),
     *                     @OA\Property(
     *                         property="updated_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2024-09-05T16:26:27.000000Z",
     *                     ),
     *                     @OA\Property(
     *                         property="deleted_at",
     *                         type="string",
     *                         nullable=true,
     *                         example=null,
     *                     ),
     *                     @OA\Property(
     *                         property="children",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="id",
     *                                 type="integer",
     *                                 example=9,
     *                             ),
     *                             @OA\Property(
     *                                 property="name",
     *                                 type="string",
     *                                 example="Category 3",
     *                             ),
     *                             @OA\Property(
     *                                 property="slug",
     *                                 type="string",
     *                                 example="",
     *                             ),
     *                             @OA\Property(
     *                                 property="image",
     *                                 type="string",
     *                                 example="https://picsum.photos/200/300?random=3",
     *                             ),
     *                             @OA\Property(
     *                                 property="parent_id",
     *                                 type="integer",
     *                                 example=3,
     *                             ),
     *                             @OA\Property(
     *                                 property="created_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2024-09-05T16:26:27.000000Z",
     *                             ),
     *                             @OA\Property(
     *                                 property="updated_at",
     *                                 type="string",
     *                                 format="date-time",
     *                                 example="2024-09-05T16:26:27.000000Z",
     *                             ),
     *                             @OA\Property(
     *                                 property="deleted_at",
     *                                 type="string",
     *                                 nullable=true,
     *                                 example=null,
     *                             ),
     *                         ),
     *                     ),
     *                 )
     *             )
     *         )
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


    public function index()
    {
        $categories = Category::query()
            ->with(['children'])
            ->where('parent_id', null)
            ->latest('id')
            ->get();


        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công danh mục',
                'categories' =>  $categories,
            ],
            200,
        );
    }


    /**
     * @OA\Get(
     *     path="/api/admin/categories/trashed",
     *     summary="Lấy danh mục đã xóa",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công danh mục đã xóa.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công danh mục đã xoá"),
     *             @OA\Property(property="trashedCategories", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Bạn cần đăng nhập để xem thông tin.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không thể lấy được danh mục đã xóa.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không thể lấy được danh mục đã xoá.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Đã xảy ra lỗi không mong muốn.",
     *         @OA\Schema(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi không mong muốn.")
     *         )
     *     ),
     * )
     */
    public function trashed()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem thông tin.'
                ], 401); // 401 Unauthorized
            }

            // Lấy tất cả danh mục đã xóa
            $trashedCategories = Category::onlyTrashed()->get();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công danh mục đã xoá',
                    'trashedCategories' => $trashedCategories,
                ],
                200,
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy được danh mục đã xoá.'
            ], 404);
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::error('Đã xảy ra lỗi: ' . $e->getMessage());

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
     *     path="/api/admin/categories",
     *     summary="Thêm danh mục mới",
     *     description="Thêm danh mục vào hệ thống.",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tên danh mục",
     *                     example="Danh mục 1"
     *                 ),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     description="Hình ảnh danh mục (đường dẫn ảnh)",
     *                     example="",
     *                 ),
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     description="ID danh mục cha (nếu có)",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Thêm danh mục thành công."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=2
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Danh mục 1"
     *                     ),
     *                     @OA\Property(
     *                         property="image",
     *                         type="string",
     *                         example="",
     *                         description="Để ở đây trống để test"
     *                     ),
     *                     @OA\Property(
     *                         property="parent_id",
     *                         type="integer",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Truy cập bị từ chối (không phải admin)",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Bạn không phải admin."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Thêm danh mục thất bại",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Thêm danh mục thất bại"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Chi tiết lỗi..."
     *             )
     *         )
     *     )
     * )
     */


    public function store(Request $request)
    {
        DB::beginTransaction();
        $currentUser = auth('api')->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.'
            ], 403); // 403 Forbidden
        }
        try {
            // Validate dữ liệu đầu vào
            $data = $request->validate([
                'name' => ['required', 'max:255', 'unique:categories,name'],
                'image' => ['nullable', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);
            $data['slug'] = Str::slug($request->name);
            // Kiểm tra và lưu ảnh nếu có
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(self::PATH_UPLOAD, 'public');
                $data['image'] =  asset('storage/' . $path);
            }
            if($data['parent_id'] == null){
                $data['parent_id'] = 0;
            }
            if ($data['parent_id']) {
                $parentID = Category::query()->find($data['parent_id']);
                if ($parentID && $parentID->parent_id  != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Danh mục cha không thể là danh mục con của một danh mục khác!',
                    ], 400);
                }

            }
            // Tạo danh mục mới
            $category = Category::query()->create($data);

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Thêm danh mục thành công.',
                    'data' => [
                        'category' => $category,
                    ],
                ],
                201
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Thêm danh mục thất bại',
                    'error' => $exception->getMessage()
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
     *     path="/api/admin/categories/{id}",
     *     summary="Lấy chi tiết danh mục",
     *     tags={"Category"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công dữ liệu của danh mục",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của bản ghi {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Category Name"),
     *                 @OA\Property(property="image", type="string", example="http://127.0.0.1:8000/storage/categories/WH9uJPvTt5VjkpDr0dE5uEkeXaFtCKaOwuAups3C.jpg"),
     *                 @OA\Property(property="parent_id", type="string", example=""),
     *                 @OA\Property(property="slug", type="string", example="category-name"),
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
     *             @OA\Property(property="message", type="string", example="Lấy dữ liệu không thành công"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Category]"),
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
//            $category = Category::findOrFail($id);
            $category = Category::query()->with(['children','products'])->get();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công dữ liệu của bản ghi ' . $id,
                    'category' => $category,
                ],
                200,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'lấy dữ liệu không thành công',
                    'error' => "Không tồn tại danh mục này."
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
     *     path="/api/admin/categories/{id}",
     *     summary="Cập nhật danh mục",
     *     description="Cập nhật thông tin của một danh mục.",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của danh mục cần cập nhật",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tên danh mục"),
     *             @OA\Property(property="image", type="string", format="binary", example="image.jpg"),
     *             @OA\Property(property="parent_id", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật danh mục thành công."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên danh mục"),
     *                 @OA\Property(property="slug", type="string", example="ten-danh-muc"),
     *                 @OA\Property(property="image", type="string", example="http://127.0.0.1:8000/storage/categories/image.jpg"),
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
     *             @OA\Property(property="message", type="string", example="Danh mục không tìm thấy"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Category]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Thông tin không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Danh mục cha không thể là danh mục con của một danh mục khác!"),
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
                'slug' => ['nullable', 'max:255', 'unique:categories,slug,' . $id],
                'image' => ['nullable', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            $model = Category::query()->findOrFail($id);

            if($data['parent_id'] == null){
                $data['parent_id'] = 0;
            }
            if ($data['parent_id']) {
                $parentID = Category::query()->find($data['parent_id']);
                if ($parentID && $parentID->parent_id != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Danh mục cha không thể là danh mục con của một danh mục khác!',
                    ], 400);
                }

            }

            if ($data['parent_id'] == null){
                $data['parent_id'] = $model->parent_id;
            }

            if ($request->has('slug')) {
                $data['slug'] = Str::slug($request->input('slug'));
            } else {
                $data['slug'] = Str::slug($request->name);
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(self::PATH_UPLOAD, 'public');
                $data['image'] = asset('storage/' . $path);
                $image_old = $model->image;
            } else {
                $image_old = null;
            }

            $model->update($data);

            if ($image_old && Storage::exists($image_old)) {
                Storage::delete($image_old);
            }

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Cập nhật danh mục thành công.',
                    'data' => $model,
                ],
                200,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Cập nhật danh mục thất bại',
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
     *     path="/api/admin/categories/{id}",
     *     summary="Xóa danh mục",
     *     tags={"Category"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần xóa",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Xóa danh mục thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa danh mục thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Không thể xóa danh mục do liên quan đến các bản ghi khác",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không thể xóa danh mục này vì nó có liên quan đến các bản ghi khác.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi không xác định",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xóa danh mục không thành công do lỗi không xác định."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */


    public function destroy(string $id)
    {
        try {
            // Tìm danh mục theo ID, nếu không tìm thấy sẽ ném ngoại lệ
            $model = Category::findOrFail($id);

            // Xóa hình ảnh nếu có
            if ($model->image && Storage::exists($model->image)) {
                Storage::delete($model->image);
            }

            // Xóa danh mục
            $model->delete();

            // Ghi log thành công
            Log::info("Danh mục với ID {$id} đã được xóa.");

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Xóa danh mục thành công.',
                ],
                204 // Trả về mã 200 cho thành công
            );

        } catch (\Illuminate\Database\QueryException $e) {
            // Kiểm tra mã lỗi để xác định loại lỗi
            if ($e->errorInfo[1] == 1451) {
                // Lỗi do khóa ngoại
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Không thể xóa danh mục này vì nó có liên quan đến các bản ghi khác.',
                    ],
                    400 // Trả về mã 400 cho lỗi yêu cầu không hợp lệ
                );
            }

            // Ghi log lỗi
            Log::error("Lỗi khi xóa danh mục ID {$id}: " . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Xóa danh mục không thành công do lỗi không xác định.',
                ],
                500 // Trả về mã 500 cho lỗi không xác định
            );
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::error("Lỗi không xác định khi xóa danh mục ID {$id}: " . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Xóa danh mục không thành công do lỗi không xác định.',
                ],
                500 // Trả về mã 500 cho lỗi không xác định
            );
        }
    }




}
