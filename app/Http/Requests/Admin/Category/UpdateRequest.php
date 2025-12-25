<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string|min:3|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($id) {
                    if (!$value) {
                        return; // Cho phép null
                    }

                    // Không cho phép set parent là chính nó
                    if ((int)$value === (int)$id) {
                        $fail('Không thể đặt danh mục làm cha của chính nó.');
                        return;
                    }

                    // Kiểm tra không cho phép set parent là con của nó
                    // Nếu category hiện tại có con, thì không được chọn bất kỳ con nào làm parent
                    if ($this->isChildOf($value, $id)) {
                        $fail('Không thể chọn danh mục con (hoặc cháu) làm danh mục cha.');
                    }
                },
            ],
            'thumbnail' => 'nullable|string',
        ];
    }

    /**
     * Kiểm tra xem $parentId có phải là con (hoặc cháu) của $currentId hay không.
     */
    protected function isChildOf($parentId, $currentId): bool
    {
        $children = Category::where('parent_id', $currentId)->pluck('id');

        foreach ($children as $childId) {
            if ((int)$childId === (int)$parentId) {
                return true;
            }
            if ($this->isChildOf($parentId, $childId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Tên danh mục
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.min' => 'Tên danh mục phải có ít nhất :min ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá :max ký tự.',

            // Slug
            'slug.required' => 'Slug là bắt buộc.',
            'slug.string' => 'Slug phải là chuỗi ký tự hợp lệ.',
            'slug.min' => 'Slug phải có ít nhất :min ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',

            // Mô tả
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.min' => 'Mô tả phải có ít nhất :min ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
            // Danh mục cha
            'parent_id.integer' => 'ID danh mục cha phải là số nguyên.',
            'parent_id.exists' => 'Danh mục cha không tồn tại trong hệ thống.',
        ];
    }
}
