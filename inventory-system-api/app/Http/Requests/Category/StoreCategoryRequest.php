<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseFormRequest;

class StoreCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255', 'unique:categories,category_name'],
            'id_category' => ['nullable', 'string', 'max:10', 'unique:categories,id_category'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Nama kategori wajib diisi.',
            'category_name.unique' => 'Nama kategori sudah digunakan.',
            'category_name.max' => 'Nama kategori maksimal 255 karakter.',
            'id_category.unique' => 'ID kategori sudah digunakan.',
            'id_category.max' => 'ID kategori maksimal 10 karakter.',
        ];
    }
}
