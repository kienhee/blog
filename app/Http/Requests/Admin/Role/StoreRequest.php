<?php

namespace App\Http\Requests\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên vai trò',
            'name.min' => 'Tên vai trò phải có ít nhất :min ký tự',
            'name.max' => 'Tên vai trò không được vượt quá :max ký tự',
            'name.unique' => 'Tên vai trò đã tồn tại trong hệ thống',
            'permissions.array' => 'Danh sách quyền không hợp lệ',
            'permissions.*.exists' => 'Một hoặc nhiều quyền không tồn tại',
        ];
    }
}

