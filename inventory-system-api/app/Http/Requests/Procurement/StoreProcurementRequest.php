<?php

namespace App\Http\Requests\Procurement;

use App\Http\Requests\BaseFormRequest;

class StoreProcurementRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_requester'           => ['required', 'exists:users,id_user'],
            'id_job_profile_target'  => ['required', 'exists:job_profiles,id_job_profile'],
            'item_name'              => ['required', 'string', 'max:255'],
            'desired_specifications' => ['required', 'string'],
            'quantity'               => ['required', 'integer', 'min:1'],
            'reason'                 => ['required', 'string'],
            'request_date'           => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_requester.required'          => 'Pemohon wajib dipilih.',
            'id_requester.exists'            => 'Pemohon tidak valid.',
            'id_job_profile_target.required' => 'Job Profile target wajib dipilih.',
            'id_job_profile_target.exists'   => 'Job Profile tidak ditemukan.',
            'item_name.required'             => 'Nama barang wajib diisi.',
            'desired_specifications.required'=> 'Spesifikasi yang diinginkan wajib diisi.',
            'quantity.min'                   => 'Jumlah minimal 1.',
            'reason.required'                => 'Alasan pengadaan wajib diisi.',
            'request_date.date'              => 'Format tanggal tidak valid.',
        ];
    }
}
