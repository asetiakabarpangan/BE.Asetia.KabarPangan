<?php

namespace App\Http\Requests\Location;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Location;

class UpdateLocationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locationId = $this->route('location');
        return [
            'location_name' => ['sometimes', 'string', 'max:255'],
            'id_location' => ['sometimes', 'string', 'max:10', 'unique:locations,id_location,' . $locationId . ',id_location'],
            'building' => ['sometimes', 'string', 'max:255'],
            'id_person_in_charge' => ['sometimes', Rule::exists('users', 'id_user')->whereIn('role', ['admin', 'moderator'])],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('id_person_in_charge')) {
                return;
            }
            $locationId = $this->route('location');
            $userId = $this->id_person_in_charge;
            $user = User::where('id_user', $userId)->first();
            if (!$user) {
                return;
            }
            if ($user->role === 'moderator') {
                $alreadyAssigned = Location::where('id_person_in_charge', $userId)
                    ->where('id_location', '!=', $locationId)
                    ->exists();
                if ($alreadyAssigned) {
                    $validator->errors()->add(
                        'id_person_in_charge',
                        'Moderator ini sudah menjadi penanggung jawab di lokasi lain.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'location_name.max' => 'Nama lokasi tidak boleh lebih dari 255 karakter.',
            'id_location.unique' => 'ID yang dibuat telah digunakan. Coba lagi atau gunakan ID otomatis.',
            'id_location.max' => 'ID lokasi tidak boleh lebih dari 10 karakter.',
            'building.max' => 'Nama gedung tidak boleh lebih dari 255 karakter.',
            'id_person_in_charge.exists' => 'ID pengguna tidak ditemukan atau bukan admin/moderator.',
        ];
    }
}
