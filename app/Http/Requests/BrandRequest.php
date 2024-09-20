<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BrandRequest extends FormRequest
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
            'message' => 'Lỗi thêm thương hiệu',
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
            'name' => ['required', 'max:255'],
            'image' => ['nullable', 'mimes:jpeg,jpg,png,svg,webp', 'max:1500'],
            'description' => ['nullable', 'max:255'],
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập thương hiệu',
            'name.max' => 'Vui lòng nhập thương hiệu < 255 ký tự',
            'image.mimes' => 'Hình ảnh phải là định dạng jpeg, jpg, png, svg, webp',
            'image.max' => 'Hình ảnh phải nhỏ hơn 1500KB',
            'description.max' => 'Mô tả thương hiệu phải nhiều hơn 255 ký tự',
            

        ];
    }
}