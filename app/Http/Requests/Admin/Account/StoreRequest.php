<?php

namespace App\Http\Requests\Admin\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'type' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'note' => 'nullable|string',
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
            'type.string' => 'Loại tài khoản phải là chuỗi ký tự',
            'type.max' => 'Loại tài khoản không được vượt quá 255 ký tự',
            'name.string' => 'Tên tài khoản phải là chuỗi ký tự',
            'name.max' => 'Tên tài khoản không được vượt quá 255 ký tự',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự',
            'note.string' => 'Ghi chú phải là chuỗi ký tự',
        ];
    }
}

