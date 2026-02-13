<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAll(): Collection
    {
        return User::select('id_user', 'name')->get();
    }

    public function getUserFilters(array $filters): Collection
    {
        $query = User::select('id_user', 'name', 'email', 'position', 'role', 'id_department', 'id_job_profile');
        $roles = $filters['role'] ?? null;
        $departments = $filters['department'] ?? null;
        $jobProfiles = $filters['job_profile'] ?? null;
        if ($roles) {
            if (is_array($roles)) {
                $query->whereIn('role', $roles);
            } else {
                $query->where('role', $roles);
            }
        }
        if ($departments) {
            if (is_array($departments)) {
                $query->whereIn('id_department', $departments);
            } else {
                $query->where('id_department', $departments);
            }
        }
        if ($jobProfiles) {
            if (is_array($jobProfiles)) {
                $query->whereIn('id_job_profile', $jobProfiles);
            } else {
                $query->where('id_job_profile', $jobProfiles);
            }
        }
        return $query->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = User::select('users.*')->with(['department', 'jobProfile']);
        $search = $filters['search'] ?? null;
        $department = $filters['department'] ?? null;
        $jobProfile = $filters['job_profile'] ?? null;
        $role = $filters['role'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('position', 'ilike', "%{$search}%")
                ->orWhere('id_user', 'ilike', "%{$search}%");
            });
        }
        if ($department) {
            $query->where('id_department', $department);
        }
        if ($jobProfile) {
            $query->where('id_job_profile', $jobProfile);
        }
        if ($role) {
            $query->where('role', $role);
        }
        $allowedSorts = ['name', 'email', 'role', 'position', 'id_department', 'id_job_profile', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function find(string $id): ?User
    {
        return User::find($id)->load(['department', 'jobProfile']);
    }

    public function findWithLoans(string $id)
    {
        return User::find($id)?->loans()
        ->select('id_loan', 'id_asset', 'loan_status')
        ->latest()->limit(10)->get();
    }

    public function findWithMaintenancesOfficer(string $id)
    {
        return User::find($id)?->maintenancesOfficer()
        ->select('id_maintenance', 'id_asset', 'maintenance_status')
        ->latest()->limit(10)->get();
    }

    public function findWithProcurementsRequester(string $id)
    {
        return User::find($id)?->procurementsRequester()
        ->select('id_procurement', 'item_name', 'procurement_status')
        ->latest()->limit(10)->get();
    }

    public function findWithProcurementsApprover(string $id)
    {
        return User::find($id)?->procurementsApprover()
        ->select('id_procurement', 'item_name', 'procurement_status')
        ->latest()->limit(10)->get();
    }

    public function delete(array $request, User $user): void
    {
        $admin = Auth::user();
        if (!Hash::check($request['account_password'], $admin->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusPengguna321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if ($user->loans()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus pengguna yang masih memiliki pinjaman.');
        }
        if ($user->maintenancesOfficer()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus pengguna yang masih memiliki pemeliharaan.');
        }
        if ($user->procurementsRequester()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus pengguna yang masih memiliki pengajuan procurement.');
        }
        if ($user->procurementsApprover()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus pengguna yang pernah menyetujui pengajuan procurement.');
        }
        DB::transaction(function () use ($user) {
            $user->delete();
        });
        DataChanged::dispatch('users', 'deleted');
    }

    public function getStatistics(): array
    {
        $totalUsers = User::count();
        $byRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();
        $byDepartment = User::selectRaw('id_department, COUNT(*) as count')
            ->whereNotNull('id_department')
            ->with('department:id_department,department_name')
            ->groupBy('id_department')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        $byJobProfile = User::selectRaw('id_job_profile, COUNT(*) as count')
            ->whereNotNull('id_job_profile')
            ->with('jobProfile:id_job_profile,profile_name')
            ->groupBy('id_job_profile')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        $today = User::whereDate('created_at', today())->count();
        $thisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        return [
            'total_users'    => $totalUsers,
            'new_today'      => $today,
            'new_this_week'  => $thisWeek,
            'new_this_month' => $thisMonth,
            'by_role'        => $byRole,
            'by_department'  => $byDepartment,
            'by_job_profile' => $byJobProfile,
        ];
    }
}
