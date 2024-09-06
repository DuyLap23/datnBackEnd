<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::query()->with(['children'])->where('parent_id', null)->get();
        $categoryParent = Category::query()->where('parent_id', null)->get();
        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => ['categories' => $categories, 'categoryParen' => $categoryParent]
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();
            Category::query()->create($data);
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Category created successfully.',
                    'data' => $data,
                ],
                201,
            );
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $exception->getMessage(),

                ],500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
