<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
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
            'id' => 'required|integer|exists:categories,id',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $categoryId = $this->input('id');

                    if (!$value) {
                        return; // Cho phép null
                    }

                    // Không cho phép set parent là chính nó
                    if ((int)$value === (int)$categoryId) {
                        $fail('Không thể đặt danh mục làm cha của chính nó.');
                        return;
                    }

                    // Kiểm tra không cho phép set parent là con của nó
                    // Nếu category hiện tại có con, thì không được chọn bất kỳ con nào làm parent
                    if ($this->isDescendant($categoryId, $value)) {
                        $fail('Không thể chọn danh mục con (hoặc cháu) làm danh mục cha.');
                        return;
                    }
                },
            ],
            'position' => 'nullable|integer|min:0', // Vị trí mới trong parent (0-based index)
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->input('id');
            $newParentId = $this->input('parent_id');

            // Kiểm tra category có tồn tại không
            $category = Category::find($categoryId);
            if (!$category) {
                $validator->errors()->add('id', 'Danh mục không tồn tại.');
                return;
            }
        });
    }

    /**
     * Kiểm tra xem $targetId có phải là con (descendant) của $ancestorId không
     *
     * @param int $ancestorId
     * @param int $targetId
     * @return bool
     */
    protected function isDescendant($ancestorId, $targetId): bool
    {
        // Lấy tất cả các con của ancestorId
        $children = Category::where('parent_id', $ancestorId)->pluck('id');

        foreach ($children as $childId) {
            // Nếu targetId là con trực tiếp
            if ((int)$childId === (int)$targetId) {
                return true;
            }

            // Kiểm tra đệ quy: targetId có phải là con của child không
            if ($this->isDescendant($childId, $targetId)) {
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
            // ID danh mục
            'id.required' => 'ID danh mục là bắt buộc.',
            'id.integer' => 'ID danh mục phải là số nguyên.',
            'id.exists' => 'Danh mục không tồn tại trong hệ thống.',

            // Danh mục cha
            'parent_id.integer' => 'ID danh mục cha phải là số nguyên.',
            'parent_id.exists' => 'Danh mục cha không tồn tại trong hệ thống.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id' => 'danh mục',
            'parent_id' => 'danh mục cha',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();
        $message = !empty($errors) ? $errors[0] : 'Dữ liệu không hợp lệ.';

        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => $message,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

