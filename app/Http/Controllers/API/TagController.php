<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequests;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = Tag::query()->get();
        return response()->json([
            'message' => 'Danh sách tag.',
            'data' => $tags
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TagRequests $request)
    {
        DB::begintransaction();
        try {
            $data = $request->validated();
            Tag::query()->create($data);
            DB::commit();
            return response()->json([
                'message' => 'Thêm tag thành công.',

            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),

            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tag = Tag::query()->findOrFail($id);
        return response()->json([
            'message' => 'Lấy thành công tag' . ' ' . $id,
            'data' => $tag
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TagRequests $request, string $id)
    {
        DB::begintransaction();
        try {
            $tag = Tag::query()->findOrFail($id);
            $data = $request->validated();
            $tag->update($data);
            DB::commit();
            return response()->json([
                'message' => 'Cập nhật tag thành công.',

            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $tag = Tag::query()->findOrFail($id);

            $tag->delete();

            return response()->json([
                'message' => 'Xóa tag thành công.',
            ], 200);
        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                    'message' => $e->getMessage(),
                ], 500);
        }
    }
}
