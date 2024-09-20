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
        ];
    }
}