<?php

namespace App\Http\Requests\DataPort;

use App\Http\Requests\BaseFormRequest;

class ImportDataRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'table' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls)',
            'file.required' => 'Silakan upload file Excel terlebih dahulu',
        ];
    }
}
