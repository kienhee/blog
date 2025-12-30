<?php

namespace App\Http\Requests\Client\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'scroll_percentage' => 'nullable|numeric|min:0|max:100',
            'time_on_page' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'scroll_percentage.numeric' => 'Dữ liệu scroll không hợp lệ.',
            'time_on_page.integer' => 'Thời gian trên trang không hợp lệ.',
        ];
    }
}

