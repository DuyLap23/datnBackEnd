<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CateController extends Controller
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      
        $data = $request->all();
        Category::query()->create($data);

        return response()->json(
            [
                'success' => true,
                'message' => 'Room created successfully.',
                'data' => $data,
            ],
            201,
        );
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
