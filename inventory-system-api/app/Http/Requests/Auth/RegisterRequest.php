<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'position' => 'required|string|max:255',
            'id_department' => 'required|exists:departments,id_department',
            'id_job_profile' => 'required|exists:job_profiles,id_job_profile',
            'role' => 'nullable|in:admin,employee',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'position.required' => 'Posisi wajib diisi.',
            'id_department.required' => 'Departemen wajib diisi.',
            'id_department.exists' => 'Departemen tidak valid.',
            'id_job_profile.required' => 'Job profile wajib diisi.',
            'id_job_profile.exists'  => 'Job profile tidak valid.',
            'role.in' => 'Role harus admin atau employee.',
        ];
    }
}
