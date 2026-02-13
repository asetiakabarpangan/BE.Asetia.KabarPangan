<?php

namespace App\Http\Requests\JobProfile;

use App\Http\Requests\BaseFormRequest;

class UpdateJobProfileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobProfileId = $this->route('job_profile');
        return [
            'profile_name' => ['required', 'string', 'max:255', 'unique:job_profiles,profile_name,' . $jobProfileId . ',id_job_profile'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_name.required' => 'Nama profil pekerjaan wajib diisi.',
            'profile_name.unique' => 'Nama profil pekerjaan sudah ada.',
            'profile_name.max' => 'Nama profil pekerjaan maksimal 255 karakter.',
        ];
    }
}
