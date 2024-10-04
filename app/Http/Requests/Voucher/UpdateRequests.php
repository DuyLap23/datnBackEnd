<?php

namespace App\Http\Requests\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'minimum_order_value' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === 'percent' && $value > 100) {
                        $fail('Giá trị giảm giá không được vượt quá 100%.');
                    }
                }
            ],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
