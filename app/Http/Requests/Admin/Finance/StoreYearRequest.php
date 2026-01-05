<?php

namespace App\Http\Requests\Admin\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreYearRequest extends FormRequest
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
            'year' => [
                'required',
                'integer',
                'min:2026',
                'max:2100',
                Rule::unique('finance_years', 'year')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                }),
            ],
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
            'year.required' => 'Vui lòng nhập năm',
            'year.integer' => 'Năm phải là số nguyên',
            'year.min' => 'Năm phải lớn hơn hoặc bằng 2026',
            'year.max' => 'Năm phải nhỏ hơn hoặc bằng 2100',
            'year.unique' => 'Năm này đã tồn tại trong hệ thống',
        ];
    }
}

