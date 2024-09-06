<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    const PATH_UPLOAD = 'categories';
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
       
            $data = $request->validate([
                'name' => ['require', 'max:255'],
                'image' => ['require', 'mime:jpeg,jpg,png,svg,webp', 'max:1500'],
                'parent_id'=> ['nullable', 'exists:categories,id'],
            ]);
            DB::beginTransaction();

             if ($request->hasFile('image')) {
                $data['image'] = Storage::put(self::PATH_UPLOAD, $request->file('image'));
            }
        
            Category::query()->create($data);
            DB::commit();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Category created successfully.',
                ],
                201,
            );
        } catch (\Exception $exception) {
            DB::rollBack();
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
