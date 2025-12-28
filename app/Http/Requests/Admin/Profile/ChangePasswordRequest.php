<?php

namespace App\Http\Requests\Admin\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'currentPassword' => ['required', 'string', 'max:255'],
            'newPassword' => [
                'required',
                'string',
                'min:6',
                'max:255',
                'confirmed',
            ],
            'newPassword_confirmation' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'currentPassword.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'currentPassword.string' => 'Mật khẩu hiện tại phải là chuỗi ký tự.',

            'newPassword.required' => 'Vui lòng nhập mật khẩu mới.',
            'newPassword.string' => 'Mật khẩu mới phải là chuỗi ký tự.',
            'newPassword.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
            'newPassword.max' => 'Mật khẩu mới không được vượt quá :max ký tự.',
            'newPassword.confirmed' => 'Mật khẩu xác nhận không khớp.',

            'newPassword_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới.',
            'newPassword_confirmation.string' => 'Mật khẩu xác nhận phải là chuỗi ký tự.',
        ];
    }
}

