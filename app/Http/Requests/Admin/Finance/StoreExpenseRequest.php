<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
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
            'date' => 'required|date',
            'finance_type_id' => 'required|exists:finance_type,id',
            'money' => 'required|string',
            'note' => 'nullable|string|max:255',
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
            'date.required' => 'Vui lòng chọn ngày',
            'date.date' => 'Ngày không hợp lệ',
            'finance_type_id.required' => 'Vui lòng chọn loại chi tiêu',
            'finance_type_id.exists' => 'Loại chi tiêu không tồn tại',
            'money.required' => 'Vui lòng nhập số tiền',
            'note.string' => 'Ghi chú phải là chuỗi ký tự',
            'note.max' => 'Ghi chú không được vượt quá 255 ký tự',
        ];
    }
}

