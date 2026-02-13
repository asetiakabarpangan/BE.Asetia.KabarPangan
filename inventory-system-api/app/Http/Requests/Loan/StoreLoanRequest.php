<?php

namespace App\Http\Requests\Loan;

use App\Http\Requests\BaseFormRequest;

class StoreLoanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asset' => ['required', 'exists:assets,id_asset'],
            'id_user' => ['required', 'exists:users,id_user'],
            'borrow_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:borrow_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.required' => 'Aset wajib dipilih.',
            'id_asset.exists' => 'Aset tidak ditemukan.',
            'id_user.required' => 'Peminjam wajib dipilih.',
            'id_user.exists' => 'User tidak valid.',
            'borrow_date.required' => 'Tanggal pinjam wajib diisi.',
            'borrow_date.date' => 'Format tanggal pinjam tidak valid.',
            'due_date.required' => 'Tanggal kembali wajib diisi.',
            'due_date.date' => 'Format tanggal kembali tidak valid.',
            'due_date.after' => 'Tanggal kembali harus setelah tanggal pinjam.',
        ];
    }
}
