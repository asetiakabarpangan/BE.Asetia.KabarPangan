<?php

namespace App\Http\Requests\Recommendation;

use App\Http\Requests\BaseFormRequest;

class StoreRecommendationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asset'       => ['required', 'exists:assets,id_asset'],
            'id_job_profile' => ['required', 'exists:job_profiles,id_job_profile'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.required'       => 'Aset wajib dipilih.',
            'id_asset.exists'         => 'Aset tidak ditemukan.',
            'id_job_profile.required' => 'Profil pekerjaan wajib dipilih.',
            'id_job_profile.exists'   => 'Profil pekerjaan tidak ditemukan.',
        ];
    }
}
