<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\BaseFormRequest;

class StoreDepartmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_name' => ['required', 'string', 'max:255', 'unique:departments,department_name'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_name.required' => 'Nama departemen wajib diisi.',
            'department_name.unique' => 'Nama departemen sudah ada.',
            'department_name.max' => 'Nama departemen maksimal 255 karakter.',
        ];
    }
}
