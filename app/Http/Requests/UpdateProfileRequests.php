<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequests extends FormRequest
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
    public function rules(): array
    {
        return [
            'avatar' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'link_fb' => 'string|max:255',
            'link_tt' => 'string|max:255',
            'addresses' => 'array',
            'addresses.*.id' => 'integer|exists:addresses,id',
            'addresses.*.address_name' => 'string|max:255|nullable',
            'addresses.*.phone_number' => 'required|string',
            'addresses.*.city' => 'required|string|max:255',
            'addresses.*.district' => 'required|string|max:255',
            'addresses.*.ward' => 'required|string|max:255',
            'addresses.*.detail_address' => 'required|string|max:255',
            'addresses.*.is_default' => 'boolean|nullable',
        ];
    }
}
