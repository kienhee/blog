<?php

namespace App\Http\Requests\Admin\Comment;

use Illuminate\Foundation\Http\FormRequest;

class ReplyRequest extends FormRequest
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
            'content' => 'required|string|min:3|max:5000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Vui lòng nhập nội dung trả lời.',
            'content.string' => 'Nội dung phải là chuỗi ký tự.',
            'content.min' => 'Nội dung trả lời phải có ít nhất :min ký tự.',
            'content.max' => 'Nội dung trả lời không được vượt quá :max ký tự.',
        ];
    }
}

