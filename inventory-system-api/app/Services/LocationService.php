<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\Location;
use App\Helpers\IdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocationService
{
    public function getAll(): Collection
    {
        return Location::all();
    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Location::select('locations.*')->with('personInCharge');
        $search = $filters['search'] ?? null;
        $filterPersonInCharge = $filters['personInCharge'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('location_name', 'ilike', "%{$search}%")
                ->orWhere('id_location', 'ilike', "%{$search}%")
                ->orWhere('building', 'ilike', "%{$search}%");
            });
        }
        if ($filterPersonInCharge) {
            $query->where('person_in_charge', 'like', "%{$filterPersonInCharge}%");
        }
        $allowSortBy = ['id_location', 'location_name', 'building', 'person_in_charge', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Location
    {
        return Location::find($id);
    }

    public function findWithPersonInCharge(string $id)
    {
        return Location::find($id)?->personInCharge()
            ->select('name', 'email', 'position', 'role', 'id_department', 'id_job_profile')
            ->first();
    }

    public function findWithAssets(string $id)
    {
        return Location::find($id)?->assets()
            ->select('id_asset', 'asset_name', 'brand', 'condition', 'availability_status')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function suggestId(string $building): array
    {
        return IdGenerator::suggestLocationId($building);
    }

    public function create(array $data): Location
    {
        $location = DB::transaction(function () use ($data) {
            $locationId = $data['id_location'] ?? IdGenerator::generateLocationId($data['building']);
            if (Location::where('id_location', $locationId)->exists()) {
                throw new \Exception('ID lokasi telah digunakan. Silakan coba lagi.');
            }
            $locationData = array_merge($data, ['id_location' => $locationId]);
            $location = Location::create($locationData);
            return $location->fresh();
        });
        DataChanged::dispatch('locations', 'created', 'all');
        return $location;
    }

    public function update(Location $location, array $data): Location
    {
        $updatedLocation = DB::transaction(function () use ($location, $data) {
            $location->update($data);
            return $location->fresh();
        });
        DataChanged::dispatch('locations', 'updated', 'all');
        return $updatedLocation;
    }

    public function delete(array $request, Location $location): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusLokasi321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if ($location->assets()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus lokasi yang masih memiliki aset.');
        }
        DB::transaction(function () use ($location) {
            $location->delete();
        });
        DataChanged::dispatch('locations', 'deleted', 'all');
    }
}
