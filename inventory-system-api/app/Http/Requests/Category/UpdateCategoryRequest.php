<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseFormRequest;

class UpdateCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');
        return [
            'category_name' => ['sometimes', 'string', 'max:255', 'unique:categories,category_name,' . $categoryId . ',id_category'],
            'id_category' => ['sometimes', 'string', 'max:10', 'unique:categories,id_category,' . $categoryId . ',id_category',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.unique' => 'Nama kategori sudah digunakan.',
            'category_name.max' => 'Nama kategori maksimal 255 karakter.',
            'id_category.unique' => 'ID kategori sudah digunakan.',
            'id_category.max' => 'ID kategori maksimal 10 karakter.',
        ];
    }
}
