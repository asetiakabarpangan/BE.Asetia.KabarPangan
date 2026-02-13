<?php

namespace App\Http\Requests\Asset;

use App\Http\Requests\BaseFormRequest;

class StoreAssetRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id_location' => (string) $this->id_location,
            'id_category' => (string) $this->id_category,
        ]);
    }

    public function rules(): array
    {
        return [
            'id_asset' => ['nullable', 'string', 'max:50', 'unique:assets,id_asset'],
            'id_category' => ['required', 'exists:categories,id_category'],
            'asset_name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'specification' => ['sometimes', 'array'],
            'id_location' => ['required', 'exists:locations,id_location'],
            'condition' => ['required', 'in:Baik,Rusak,Hilang'],
            'acquisition_date' => ['required', 'date'],
            'availability_status' => ['required', 'in:Tersedia,Dipinjam,Dalam Perbaikan'],
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
            'id_asset.unique' => 'ID Aset sudah digunakan. Gunakan ID lain atau biarkan kosong untuk generate otomatis.',
            'id_asset.max' => 'ID Aset tidak boleh lebih dari 50 karakter.',
            'id_category.required' => 'Kategori aset wajib dipilih.',
            'id_category.exists' => 'Kategori aset tidak valid.',
            'asset_name.required' => 'Nama aset wajib diisi.',
            'asset_name.max' => 'Nama aset tidak boleh lebih dari 255 karakter.',
            'brand.required' => 'Merk/Brand wajib diisi.',
            'brand.max' => 'Merk/Brand tidak boleh lebih dari 255 karakter.',
            'specification.required' => 'Spesifikasi aset wajib diisi (format array).',
            'specification.array' => 'Format spesifikasi tidak valid.',
            'id_location.required' => 'Lokasi aset wajib dipilih.',
            'id_location.exists' => 'Lokasi aset tidak valid.',
            'condition.required' => 'Kondisi aset wajib dipilih.',
            'condition.in' => 'Kondisi harus salah satu dari: Baik, Rusak, atau Hilang.',
            'acquisition_date.required' => 'Tanggal perolehan wajib diisi.',
            'acquisition_date.date' => 'Format tanggal perolehan tidak valid.',
            'availability_status.required' => 'Status ketersediaan wajib dipilih.',
            'availability_status.in' => 'Status harus salah satu dari: Tersedia, Dipinjam, atau Dalam Perbaikan.',
            'images.array' => 'Format gambar harus berupa array.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
        ];
    }
}
