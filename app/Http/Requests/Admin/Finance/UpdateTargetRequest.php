<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTargetRequest extends FormRequest
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
            'target' => 'nullable|array',
            'target.*.name' => 'required_with:target|string|max:255',
            'target.*.completed' => 'boolean',
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
            'target.array' => 'Mục tiêu phải là mảng',
            'target.*.name.required_with' => 'Tên mục tiêu là bắt buộc',
            'target.*.name.string' => 'Tên mục tiêu phải là chuỗi ký tự',
            'target.*.name.max' => 'Tên mục tiêu không được vượt quá 255 ký tự',
            'target.*.completed.boolean' => 'Trạng thái hoàn thành phải là true hoặc false',
        ];
    }
}

