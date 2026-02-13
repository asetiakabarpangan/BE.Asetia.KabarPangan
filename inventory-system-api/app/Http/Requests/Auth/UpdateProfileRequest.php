<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class UpdateProfileRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id_user;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $userId . ',id_user',
            'position' => 'sometimes|string|max:255',
            'id_job_profile' => 'nullable|exists:job_profiles,id_job_profile',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan.',
            'position.max' => 'Posisi maksimal 255 karakter.',
            'id_job_profile.exists' => 'Job profile tidak valid.',
        ];
    }
}
