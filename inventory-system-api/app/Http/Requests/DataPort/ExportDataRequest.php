<?php

namespace App\Http\Requests\DataPort;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

class ExportDataRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tables'   => 'required|array',
            'tables.*' => 'string',
            'format'   => 'sometimes|in:xlsx,pdf',
            'mode'     => 'sometimes|in:standard,grouped',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('mode') === 'grouped') {
                $tables = $this->input('tables');
                if (count($tables) !== 1) {
                    $validator->errors()->add('tables', 'Mode Grouped hanya mengizinkan export 1 tabel dalam satu waktu.');
                }
                $allowedGrouped = ['assets', 'loans', 'maintenances'];
                if (!in_array($tables[0], $allowedGrouped)) {
                    $validator->errors()->add('tables', 'Mode Grouped hanya tersedia untuk Assets, Loans, atau Maintenances.');
                }
            }
        });
    }
}
