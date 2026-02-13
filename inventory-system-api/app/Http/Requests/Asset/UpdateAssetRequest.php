<?php

namespace App\Http\Requests\Asset;

use App\Http\Requests\BaseFormRequest;

class UpdateAssetRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset');
        return [
            'id_asset' => ['sometimes', 'string', 'max:50', 'unique:assets,id_asset,' . $assetId . ',id_asset'],
            'id_category' => ['sometimes', 'exists:categories,id_category'],
            'asset_name' => ['sometimes', 'string', 'max:255'],
            'brand' => ['sometimes', 'string', 'max:255'],
            'specification' => ['sometimes', 'array'],
            'id_location' => ['sometimes', 'exists:locations,id_location'],
            'condition' => ['sometimes', 'in:Baik,Rusak,Hilang'],
            'acquisition_date' => ['sometimes', 'date'],
            'availability_status' => ['sometimes', 'in:Tersedia,Dipinjam,Dalam Perbaikan'],
            'information' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif'],
            'image_description' => ['nullable', 'array'],
            'image_description.*' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_asset.unique' => 'ID Aset sudah digunakan oleh aset lain.',
            'id_asset.max' => 'ID Aset tidak boleh lebih dari 50 karakter.',
            'id_category.exists' => 'Kategori aset tidak valid.',
            'asset_name.max' => 'Nama aset tidak boleh lebih dari 255 karakter.',
            'brand.max' => 'Merk/Brand tidak boleh lebih dari 255 karakter.',
            'specification.array' => 'Format spesifikasi tidak valid.',
            'id_location.exists' => 'Lokasi aset tidak valid.',
            'condition.in' => 'Kondisi harus salah satu dari: Baik, Rusak, atau Hilang.',
            'acquisition_date.date' => 'Format tanggal perolehan tidak valid.',
            'availability_status.in' => 'Status harus salah satu dari: Tersedia, Dipinjam, atau Dalam Perbaikan.',
            'images.array' => 'Format gambar harus berupa array.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
        ];
    }
}
