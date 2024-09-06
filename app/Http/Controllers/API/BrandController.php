<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::all();
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công danh sách thương hiệu',
                'data' => $brands,
            ],
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
    
        try {
            $data = $request->validate([
                'name' => [ 'max:255'],
                'image' => [ 'nullable','mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
                'description' => [ 'nullable', 'max:255'],
            ]);
    
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('brands', 'public');
            }
            // dump($data);
            Brand::query()->create($data);
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Thêm thương hiệu thành công.',
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Thêm thương hiệu thất bại',
                    'error' => $exception->getMessage()
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Lấy thành công dữ liệu của thương hiệu ' . $id,
                    'data' => $brand,
                ],
                200,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
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
                $data['image'] = $request->file('image')->store('brands', 'public');

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
                    'success' => true,
                    'message' => 'Cập nhật thương hiệu thành công.',
                    'data' => $brand,
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
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
    public function destroy(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $brand->delete();

            if ($brand->image && Storage::exists($brand->image)) {
                Storage::delete($brand->image);
            }

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Xoá thương hiệu thành công.',
                ],
                200,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Xoá thương hiệu không thành công.',
                    'error' => $e->getMessage()
                ],
                500,
            );
        }
    }
}