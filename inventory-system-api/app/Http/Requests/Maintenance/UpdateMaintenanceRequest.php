<?php

namespace App\Http\Requests\Maintenance;

use App\Http\Requests\BaseFormRequest;

class UpdateMaintenanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_maintenance_officer' => ['sometimes', 'exists:users,id_user'],
            'maintenance_date'       => ['sometimes', 'date'],
            'maintenance_detail'     => ['sometimes', 'string'],
            'maintenance_cost'       => ['nullable', 'numeric', 'min:0'],
            'maintenance_status'     => ['sometimes', 'in:Dalam Perbaikan,Selesai'],
            'finish_date'            => ['nullable', 'date', 'after_or_equal:maintenance_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_maintenance_officer.exists' => 'Petugas tidak valid.',
            'maintenance_date.date'         => 'Format tanggal tidak valid.',
            'maintenance_cost.numeric'      => 'Biaya perbaikan harus berupa angka.',
            'maintenance_cost.min'          => 'Biaya perbaikan minimal 0.',
            'maintenance_status.in'         => 'Status perbaikan harus dalam bentuk "Dalam Perbaikan" atau "Selesai".',
            'finish_date.date'              => 'Format tanggal tidak valid.',
            'finish_date.after_or_equal'    => 'Tanggal selesai harus setelah tanggal perbaikan.',
        ];
    }
}
