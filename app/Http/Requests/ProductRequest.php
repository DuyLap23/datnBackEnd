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
            'description' => 'required',
            'price' => 'required|numeric',
            'is_active' => 'required|boolean',
            'is_new' => 'required|boolean',
            'is_show_home' => 'required|boolean',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'image' => 'required|mimes:jpeg,jpg,png,svg,webp|max:1500',
            'product_variants' => 'required|array',
            'product_variants.*.product_size_id' => 'required|exists:product_sizes,id',
            'product_variants.*.product_color_id' => 'required|exists:product_colors,id',
            'product_variants.*.quantity' => 'required|numeric',
            'product_variants.*.image' => 'mimes:jpeg,jpg,png,svg,webp|max:1500',
            'content' => 'required',
            'user_manual' => 'required',
            'view' => 'required|numeric',
            'img_thumbnail' => 'required|mimes:jpeg,jpg,png,svg,webp|max:1500',
            'price_regular' => 'required|numeric',
            'price_sale' => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm',
            'name.max' => 'Vui lòng nhập tên sản phẩm < 255 ký tự',
            'description.required' => 'Vui lòng nhập mô tả sản phẩm',
            'price.required' => 'Vui lòng nhập giá sản phẩm',
            'price.numeric' => 'Giá sản phẩm phải là số',
            'is_active.required' => 'Vui lòng chọn trạng thái hoạt động',
            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false',
            'is_new.required' => 'Vui lòng chọn trạng thái mới',
            'is_new.boolean' => 'Trạng thái mới phải là true hoặc false',
            'is_show_home.required' => 'Vui lòng chọn trạng thái hiển thị trên trang chủ',
            'is_show_home.boolean' => 'Trạng thái hiển thị trên trang chủ phải là true hoặc false',
            'brand_id.required' => 'Vui lòng chọn thương hiệu',
            'brand_id.exists' => 'Thương hiệu không tồn tại',
            'category_id.required' => 'Vui lòng chọn danh mục',
            'category_id.exists' => 'Danh mục không tồn tại',
            'image.required' => 'Vui lòng chọn hình ảnh',
            'image.mimes' => 'Hình ảnh phải là định dạng jpeg, jpg, png, svg, webp',
            'image.max' => 'Hình ảnh phải nhỏ hơn 1500KB',
            'product_variants.required' => 'Vui lòng nhập biến thể sản phẩm',
            'product_variants.array' => 'Biến thể sản phẩm phải là mảng',
            'product_variants.*.product_size_id.required' => 'Vui lòng chọn kích thước sản phẩm',
            'product_variants.*.product_size_id.exists' => 'Kích thước sản phẩm không tồn tại',
            'product_variants.*.product_color_id.required' => 'Vui lòng chọn màu sản phẩm',
            'product_variants.*.product_color_id.exists' => 'Màu sản phẩm không tồn tại',
            'product_variants.*.quantity.required' => 'Vui lòng nhập số lượng sản phẩm',
            'product_variants.*.quantity.numeric' => 'Số lượng sản phẩm phải là số',
            'product_variants.*.image.required' => 'Vui lòng chọn hình ảnh sản phẩm',
            'product_variants.*.image.mimes' => 'Hình ảnh sản phẩm phải là định dạng jpeg, jpg, png, svg, webp',    
            'product_variants.*.image.max' => 'Hình ảnh sản phẩm phải nhỏ hơn 1500KB',
            'content.required' => 'Vui lòng nhập nội dung sản phẩm',
            'user_manual.required' => 'Vui lòng nhập hướng dẫn sử dụng sản phẩm',
            'view.required' => 'Vui lòng nhập số lượt xem sản phẩm',
            'view.numeric' => 'Số lượt xem sản phẩm phải là số',
            'img_thumbnail.required' => 'Vui lòng chọn hình ảnh thumbnail sản phẩm',
            'img_thumbnail.mimes' => 'Hình ảnh thumbnail sản phẩm phải là định dạng jpeg, jpg, png, svg, webp',
            'img_thumbnail.max' => 'Hình ảnh thumbnail sản phẩm phải nhỏ hơn 1500KB',
            'price_regular.required' => 'Vui lòng nhập giá sản phẩm thường',
            'price_regular.numeric' => 'Giá sản phẩm thường phải là số',
            'price_sale.numeric' => 'Giá sản phẩm sale phải là số',
        ];
    }
}