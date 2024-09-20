<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{

    public function index()
    {
        $variants = ProductVariant::with(['product', 'size', 'color'])->get();
        return response()->json($variants);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_size_id' => 'required|exists:product_sizes,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $variant = new ProductVariant($request->all());

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/variants', $imageName);
            $variant->image = Storage::url($path);
        }

        $variant->save();
        return response()->json($variant, 201);
    }

    public function update(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'product_size_id' => 'sometimes|required|exists:product_sizes,id',
            'product_color_id' => 'sometimes|required|exists:product_colors,id',
            'quantity' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {

            if ($variant->image) {
                Storage::delete(str_replace('/storage', 'public', $variant->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/variants', $imageName);
            $variant->image = Storage::url($path);
        }

        $variant->update($request->except('image'));
        return response()->json($variant);
    }

    public function destroy($id)
    {
        $variant = ProductVariant::findOrFail($id);
        if ($variant->image) {
            Storage::delete(str_replace('/storage', 'public', $variant->image));
        }
        $variant->delete();
        return response()->json(null, 204);
    }
}


