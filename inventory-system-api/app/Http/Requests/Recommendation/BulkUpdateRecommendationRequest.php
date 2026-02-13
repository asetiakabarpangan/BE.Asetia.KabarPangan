<?php

namespace App\Http\Requests\Recommendation;

use App\Http\Requests\BaseFormRequest;

class BulkUpdateRecommendationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asset'          => ['required', 'exists:assets,id_asset'],
            'job_profile_ids'   => ['present', 'array'],
            'job_profile_ids.*' => ['exists:job_profiles,id_job_profile'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.required'        => 'Aset wajib dipilih.',
            'id_asset.exists'          => 'Aset tidak ditemukan.',
            'job_profile_ids.array'    => 'Format daftar profil pekerjaan salah.',
            'job_profile_ids.*.exists' => 'Salah satu profil pekerjaan tidak valid.',
        ];
    }
}
