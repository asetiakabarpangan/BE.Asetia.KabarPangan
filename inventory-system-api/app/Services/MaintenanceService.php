<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\{Maintenance, Asset};
use App\Helpers\IdGenerator;
use App\Traits\ValidatesLocationAccess;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MaintenanceService
{
    use ValidatesLocationAccess;

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Maintenance::select('maintenances.*')->with(['asset.category', 'maintenanceOfficer']);
        $search = $filters['search'] ?? null;
        $category = $filters['category'] ?? null;
        $location = $filters['location'] ?? null;
        $maintenanceDate = $filters['maintenance_date'] ?? null;
        $maintenanceDateStart = $filters['maintenance_date_start'] ?? null;
        $maintenanceDateEnd = $filters['maintenance_date_end'] ?? null;
        $officer = $filters['officer_id'] ?? null;
        $maintenanceStatus = $filters['maintenance_status'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id_maintenance', 'ilike', "%{$search}%")
                ->orWhere('id_asset', 'ilike', "%{$search}%")
                ->orWhere('id_maintenance_officer', 'ilike', "%{$search}%");
            });
        }
        if ($category) {
            $query->whereHas('asset', function($q) use ($category) {
                $q->where('id_category', $category);
            });
        }
        if ($location) {
            $query->whereHas('asset', function($q) use ($location) {
                $q->where('id_location', $location);
            });
        }
        if ($maintenanceDateStart || $maintenanceDateEnd) {
            if ($maintenanceDateStart) {
                $query->whereDate('maintenance_date', '>=', $maintenanceDateStart);
            }
            if ($maintenanceDateEnd) {
                $query->whereDate('maintenance_date', '<=', $maintenanceDateEnd);
            }
        } elseif ($maintenanceDate) {
            $query->whereDate('maintenance_date', $maintenanceDate);
        }
        if ($maintenanceDate) {
            $query->where('maintenance_date', $maintenanceDate);
        }
        if ($officer) {
            $query->where('id_maintenance_officer', $officer);
        }
        if ($maintenanceStatus) {
            $query->where('maintenance_status', $maintenanceStatus);
        }
        $allowSortBy = ['id_maintenance', 'id_asset', 'id_maintenance_officer', 'maintenance_status', 'created_at', 'updated_at', 'maintenance_date', 'maintenance_cost', 'asset_name', 'officer_name'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'asset_name') {
            $query->join('assets', 'maintenances.id_asset', '=', 'assets.id_asset')
                  ->orderBy('assets.asset_name', $sortDir);
        } elseif ($sortBy === 'officer_name') {
            $query->join('users', 'maintenances.id_maintenance_officer', '=', 'users.id_user')
                  ->orderBy('users.name', $sortDir);
        } else {
            $query->orderBy('maintenances.' . $sortBy, $sortDir);
        }
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Maintenance
    {
        return Maintenance::with(['asset.category', 'asset.location', 'maintenanceOfficer'])
            ->find($id);
    }

    public function create(array $data): Maintenance
    {
        $asset = Asset::find($data['id_asset']);
        if ($asset->availability_status === 'Dalam Perbaikan') {
            throw new Exception('Aset sudah terdaftar untuk perbaikan.');
        }
        $this->ensureUserCanManageLocation($asset->id_location);
        $maintenance = DB::transaction(function () use ($data, $asset) {
            $maintenanceId = IdGenerator::generateUniqueMaintenanceId();
            $maintenanceData = array_merge($data, [
                'id_maintenance' => $maintenanceId,
                'maintenance_status' => 'Dalam Perbaikan'
            ]);
            $maintenance = Maintenance::create($maintenanceData);
            if ($asset) {
                $asset->update(['availability_status' => 'Dalam Perbaikan']);
            }
            return $maintenance->load(['asset.category', 'maintenanceOfficer']);
        });
        DataChanged::dispatch('maintenances', 'created');
        return $maintenance;
    }

    public function update(Maintenance $maintenance, array $data): Maintenance
    {
        $this->ensureUserCanManageLocation($maintenance->asset->id_location);
        $maintenance->update($data);
        DataChanged::dispatch('maintenances', 'updated');
        return $maintenance->fresh(['asset.category', 'maintenanceOfficer']);
    }

    public function complete(Maintenance $maintenance): Maintenance
    {
        $this->ensureUserCanManageLocation($maintenance->asset->id_location);
        $complete = DB::transaction(function () use ($maintenance) {
            $maintenance->asset()->update([
                'availability_status' => 'Tersedia'
            ]);
            $maintenance->update([
                'maintenance_status' => 'Selesai',
                'finish_date' => now()
            ]);
            return $maintenance->fresh(['asset.category', 'maintenanceOfficer']);
        });
        DataChanged::dispatch('maintenances', 'updated');
        return $complete;
    }

    public function delete(array $request, Maintenance $maintenance): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusPeminjaman321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        $this->ensureUserCanManageLocation($maintenance->asset->id_location);
        $maintenance->delete();
        DataChanged::dispatch('maintenances', 'deleted');
    }

    public function getAssetHistory(string $assetId): Collection
    {
        return Maintenance::with(['asset'])
            ->where('id_asset', $assetId)
            ->latest()
            ->get();
    }

    public function getOfficerHistory(string $officerId): Collection
    {
        return Maintenance::with(['maintenanceOfficer'])
            ->where('id_maintenance_officer', $officerId)
            ->latest()
            ->get();
    }

    public function getStatistics(): array
    {
        $generalStats = DB::table('maintenances')
            ->selectRaw("
                COUNT(*) as total_maintenances,
                SUM(maintenance_cost) as total_cost,
                AVG(maintenance_cost) as average_cost
            ")
            ->first();
        $today = Maintenance::whereDate('created_at', today())->count();
        $thisWeek = Maintenance::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = Maintenance::whereMonth('maintenance_date', now()->month)
            ->whereYear('maintenance_date', now()->year)
            ->count();
        $byOfficer = Maintenance::selectRaw('id_maintenance_officer, COUNT(*) as count, SUM(maintenance_cost) as total_cost')
            ->with('maintenanceOfficer:id_user,name')
            ->groupBy('id_maintenance_officer')
            ->get();
        $byAsset = Maintenance::selectRaw('id_asset, COUNT(*) as count, SUM(maintenance_cost) as total_cost')
            ->with('asset:id_asset,asset_name')
            ->groupBy('id_asset')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        return [
            'total_maintenances' => (int) $generalStats->total_maintenances,
            'total_cost' => (float) $generalStats->total_cost,
            'average_cost' => (float) $generalStats->average_cost,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'by_officer' => $byOfficer,
            'by_asset' => $byAsset,
        ];
    }
}
