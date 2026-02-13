<?php

namespace App\Traits;

use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

trait ValidatesLocationAccess
{
    protected function ensureUserCanManageLocation(string $locationId): void
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return;
        }
        if ($user->role !== 'moderator') {
             throw new AuthorizationException('Anda tidak memiliki izin untuk melakukan aksi ini.');
        }
        $location = Location::find($locationId);
        if (!$location) {
            throw new Exception('Lokasi tidak ditemukan.');
        }
        if ($location->id_person_in_charge !== $user->id_user) {
            throw new AuthorizationException('Akses Ditolak: Anda bukan penanggung jawab lokasi ini.');
        }
    }
}
