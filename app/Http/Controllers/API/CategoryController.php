<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    const PATH_UPLOAD = 'categories';

    /**
     * @OA\Get(
     * path="/api/admin/categories",
     * summary="",
     * description="Danh sách danh mục.",
     * tags={"Category"},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Tên danh mục",
     * example="Danh mục 1",
     * ),
     * @OA\Property(
     * property="image",
     * type="string",
     * description="Hình ảnh danh mục",
     * example="image.png",
     * ),
     * @OA\Property(
     * property="parent_id",
     * type="bigint",
     * description="Danh mục cha",
     * example="1",
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
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
     * example="Success",
     * ),
     * @OA\Property(
     * property="status",
     * type="string",
     * example="200",
     * ),
     * @OA\Property(
     * property="data",
     * type="object",
     * @OA\Property(
     * property="id",
     * type="integer",
     * example=1,
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * example="Danh mục 1",
     * ),
     * @OA\Property(
     * property="image",
     * type="string",
     * example="image.png",
     * ),
     *  @OA\Property(
     *  property="parent_id",
     *  type="string",
     *  example="",
     *  ),
     * ),
     * ),
     * ),
     * )
     */
    public function index()
    {
        $categories = Category::query()
            ->with(['children'])
            ->where('parent_id', null)
            ->get();
        $categoryParent = Category::query()->where('parent_id', null)->get();
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công danh mục',
                'data' => ['categories' => $categories, 'categoryParent' => $categoryParent],
            ],
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     * path="/api/admin/categories",
     * summary="Thêm danh mục mới",
     * description="Thêm danh mục vào hệ thống.",
     * tags={"Category"},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Tên danh mục",
     * example="Danh mục 1",
     * ),
     * @OA\Property(
     * property="image",
     * type="string",
     * description="Hình ảnh danh mục",
     * example="image.png",
     * ),
     * @OA\Property(
     * property="parent_id",
     * type="bigint",
     * description="ID danh mục cha",
     * example="1",
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
     * example="Thêm danh mục thành công.",
     * ),
     * @OA\Property(
     * property="data",
     * type="object",
     * @OA\Property(
     * property="category",
     * type="object",
     * @OA\Property(
     * property="id",
     * type="bigint",
     * example=1,
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * example="Danh mục 1",
     * ),
     * @OA\Property(
     * property="image_url",
     * type="string",
     * example="https://apitopdeal.shop/storage/categories/image.png",
     * ),
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=500,
     * description="Thêm danh mục thất bại",
     * @OA\JsonContent(
     * @OA\Property(
     * property="success",
     * type="boolean",
     * example=false,
     * ),
     * @OA\Property(
     * property="message",
     * type="string",
     * example="Thêm danh mục thất bại",
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
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate dữ liệu đầu vào
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['required', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            // Kiểm tra và lưu ảnh nếu có
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store(self::PATH_UPLOAD, 'public');
                $data['image'] = $path;
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
                        'image_url' => asset('storage/' . $data['image']), // Trả về URL ảnh
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
    public function show(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công dữ liệu của bản ghi ' . $id,
                    'data' => $category,
                ],
                200,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'lấy dữ liệu không thành công',
                    'error' => $exception->getMessage()
                ],
                500,
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => ['required', 'max:255'],
                'image' => ['mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id' => ['nullable', 'exists:categories,id'],
            ]);

            $model = Category::query()->findOrFail($id);

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store(self::PATH_UPLOAD, 'public');

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
                201,
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
    public function destroy(string $id)
    {
        try {

            $model = Category::findOrFail($id);

            $model->delete();

            if ($model->image && Storage::exists($model->image)) {
                Storage::delete($model->image);
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Xoá danh mục thành công.',
                ],
                200,
            );

        } catch (\Exception $e) {

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Xoá danh mục không thành công.',
                    'error' => $e->getMessage()
                ],
                500,
            );
        }
    }
}
