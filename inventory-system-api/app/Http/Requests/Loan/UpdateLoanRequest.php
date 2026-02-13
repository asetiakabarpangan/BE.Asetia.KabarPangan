<?php

namespace App\Http\Requests\Loan;

use App\Http\Requests\BaseFormRequest;

class UpdateLoanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asset' => ['sometimes', 'exists:assets,id_asset'],
            'id_user' => ['sometimes', 'exists:users,id_user'],
            'borrow_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date', 'after_or_equal:borrow_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.exists' => 'Aset tidak ditemukan.',
            'id_user.exists' => 'User tidak valid.',
            'borrow_date.date' => 'Format tanggal pinjam tidak valid.',
            'due_date.date' => 'Format tanggal kembali tidak valid.',
            'due_date.after_or_equal' => 'Tanggal kembali harus setelah tanggal pinjam.',
        ];
    }
}
