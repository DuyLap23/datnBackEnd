<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Lỗi thêm sản phẩm',
            'status' => false,
            'errors' => $validator->errors()
        ], 400));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    return [
        'name' => 'required|max:255',
        'description' => 'required|min:10',
        'is_active' => 'boolean',
        'is_new' => 'boolean',
        'is_show_home' => 'boolean',
        'brand_id' => 'required|exists:brands,id',
        'category_id' => 'required|exists:categories,id',
        'product_variants' => 'required|array',
        'product_variants.*.product_size_id' => 'required|exists:product_sizes,id',
        'product_variants.*.product_color_id' => 'required|exists:product_colors,id',
        'product_variants.*.quantity' => 'required|numeric',
        'product_variants.*.image' => 'sometimes|mimes:jpeg,jpg,png,svg,webp|max:1500', // dùng 'sometimes' để cho phép không có hình ảnh
        'content' => 'required|min:10',
        'user_manual' => 'required',
        'view' => 'nullable|numeric',
        'img_thumbnail' => 'required|mimes:jpeg,jpg,png,svg,webp|max:1500',
        'price_regular' => 'required|numeric',
        'price_sale' => 'nullable|numeric',
        // 'tags' => 'nullable|array',
        // 'tags.*' => 'nullable|exists:tags,id',
    ];
}


   
}
