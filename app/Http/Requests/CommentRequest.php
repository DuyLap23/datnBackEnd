<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'rating' => [
                'required',
                'numeric',
                'in:1,1.5,2,2.5,3,3.5,4,4.5,5'
            ],
            'content' => 'required|string|max:1000',
        ];
    }
    public function messages()
    {
        return [
            'rating.in' => 'Vui lòng chọn sao từ 1-5.',
            'content.required' => 'Nội dung không được để trống.',
        ];
    }
}
