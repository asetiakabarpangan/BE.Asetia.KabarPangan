<?php

namespace App\Http\Requests\Procurement;

use App\Http\Requests\BaseFormRequest;

class UpdateProcurementRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_job_profile_target'  => ['sometimes', 'exists:job_profiles,id_job_profile'],
            'item_name'              => ['sometimes', 'string', 'max:255'],
            'desired_specifications' => ['sometimes', 'string'],
            'quantity'               => ['sometimes', 'integer', 'min:1'],
            'reason'                 => ['sometimes', 'string'],
            'request_date'           => ['sometimes', 'date'],
            'action_date'            => ['sometimes', 'date', 'after_or_equal:request_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_job_profile_target.exists' => 'Job Profile tidak ditemukan.',
            'quantity.min'                 => 'Jumlah minimal 1.',
            'request_date.date'            => 'Format tanggal tidak valid.',
            'action_date.date'             => 'Format tanggal tidak valid.',
            'action_date.after_or_equal'   => 'Tanggal tindakan harus setelah tanggal permintaan.',
        ];
    }
}
