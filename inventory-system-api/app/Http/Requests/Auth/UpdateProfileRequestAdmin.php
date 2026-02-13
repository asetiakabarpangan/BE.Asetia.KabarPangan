<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class UpdateProfileRequestAdmin extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $userId . ',id_user',
            'role' => 'nullable|in:admin,moderator,employee',
            'position' => 'sometimes|string|max:255',
            'id_department' => 'nullable|exists:departments,id_department',
            'id_job_profile' => 'nullable|exists:job_profiles,id_job_profile',
            'id_user' => 'nullable|string|max:20|unique:users,id_user,' . $userId . ',id_user',
            'email_verified_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'role.in' => 'Role tidak valid.',
            'position.max' => 'Posisi maksimal 255 karakter.',
            'id_department.exists' => 'Departemen tidak valid.',
            'id_job_profile.exists' => 'Job profile tidak valid.',
            'id_user.max' => 'ID maksimal 20 karakter.',
            'id_user.unique' => 'ID sudah digunakan.',
            'email_verified_at.date' => 'Format tanggal verifikasi email tidak valid.',
        ];
    }
}
