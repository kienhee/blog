<?php

namespace App\Http\Requests\Admin\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:accounts,id',
            'orders.*.order' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'orders.required' => 'Danh sách thứ tự là bắt buộc',
            'orders.array' => 'Danh sách thứ tự phải là mảng',
            'orders.*.id.required' => 'ID tài khoản là bắt buộc',
            'orders.*.id.exists' => 'Tài khoản không tồn tại',
            'orders.*.order.required' => 'Thứ tự là bắt buộc',
            'orders.*.order.integer' => 'Thứ tự phải là số nguyên',
        ];
    }
}

