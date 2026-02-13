<?php

namespace App\Http\Requests\Maintenance;

use App\Http\Requests\BaseFormRequest;

class StoreMaintenanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_asset'               => ['required', 'exists:assets,id_asset'],
            'id_maintenance_officer' => ['required', 'exists:users,id_user'],
            'maintenance_date'       => ['required', 'date'],
            'maintenance_detail'     => ['required', 'string'],
            'maintenance_cost'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.required'               => 'Aset wajib dipilih.',
            'id_asset.exists'                 => 'Aset tidak ditemukan.',
            'id_maintenance_officer.required' => 'Petugas maintenance wajib dipilih.',
            'id_maintenance_officer.exists'   => 'Petugas tidak valid.',
            'maintenance_date.required'       => 'Tanggal perbaikan wajib diisi.',
            'maintenance_date.date'           => 'Format tanggal tidak valid.',
            'maintenance_detail.required'     => 'Detail kerusakan/perbaikan wajib diisi.',
            'maintenance_cost.numeric'        => 'Biaya perbaikan harus berupa angka.',
            'maintenance_cost.min'            => 'Biaya perbaikan tidak boleh negatif.',
        ];
    }
}
