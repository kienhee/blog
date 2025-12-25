<?php

namespace App\Http\Requests\Admin\Contact;

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
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Vui lòng nhập tiêu đề trả lời.',
            'subject.string' => 'Tiêu đề phải là chuỗi ký tự.',
            'subject.min' => 'Tiêu đề phải có ít nhất :min ký tự.',
            'subject.max' => 'Tiêu đề không được vượt quá :max ký tự.',
            'message.required' => 'Vui lòng nhập nội dung trả lời.',
            'message.string' => 'Nội dung phải là chuỗi ký tự.',
            'message.min' => 'Nội dung trả lời phải có ít nhất 10 ký tự.',
        ];
    }
}
