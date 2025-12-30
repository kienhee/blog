<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email của bạn.',
            'email.email' => 'Email không đúng định dạng.',
            'email.exists' => 'Email này không tồn tại trong hệ thống.',
        ];
    }
}

