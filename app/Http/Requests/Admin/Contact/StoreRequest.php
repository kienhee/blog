<?php

namespace App\Http\Requests\Admin\Contact;

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
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:75',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên',
            'full_name.min' => 'Họ tên phải có ít nhất :min ký tự',
            'full_name.max' => 'Họ tên không được vượt quá :max ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email không được vượt quá :max ký tự',
            'subject.required' => 'Vui lòng nhập tiêu đề',
            'subject.min' => 'Tiêu đề phải có ít nhất :min ký tự',
            'subject.max' => 'Tiêu đề không được vượt quá :max ký tự',
            'message.required' => 'Vui lòng nhập nội dung',
            'message.min' => 'Nội dung phải có ít nhất :min ký tự',
        ];
    }
}