<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'status' => true,
            'message' => 'Category retrieved successfully',
            'data' => ['categories' => $categories, 'categoryParen' => $categoryParent]
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

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
    public function show(String $id)
    {
        $category = Category::findOrFail($id);
        return response()->json([
            'status' => true,
            'message' => "success ",
            'data' => $category

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        $model = Category::query()->findOrFail($id);
        
        $model->delete();

        return response()->json(['status' => true, 'message' => 'success']);
    }
}
