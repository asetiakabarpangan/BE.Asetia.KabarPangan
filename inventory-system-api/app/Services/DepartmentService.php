<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DepartmentService
{
    public function getAll(): Collection
    {
        return Department::select('id_department', 'department_name')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Department::query();
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('department_name', 'ilike', "%{$search}%")
                ->orWhere('id_department', 'ilike', "%{$search}%");
            });
        }
        $allowSortBy = ['id_department', 'department_name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Department
    {
        return Department::find($id);
    }

    public function findWithUsers(string $id)
    {
        return Department::find($id)?->users()
            ->select('id_user', 'name', 'email', 'position', 'role')
            ->with('jobProfile:id_job_profile,profile_name')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function create(array $data): Department
    {
        $department = DB::transaction(function () use ($data) {
            $department = Department::create($data);
            return $department->fresh();
        });
        DataChanged::dispatch('departments', 'created', 'all');
        return $department;
    }

    public function update(Department $department, array $data): Department
    {
        $updatedDepartment = DB::transaction(function () use ($department, $data) {
            $department->update($data);
            return $department->fresh();
        });
        DataChanged::dispatch('departments', 'updated', 'all');
        return $updatedDepartment;
    }

    public function delete(array $request, Department $department): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusDepartemen321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if ($department->users()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus departemen yang masih memiliki pengguna.');
        }
        DB::transaction(function () use ($department) {
            $department->delete();
        });
        DataChanged::dispatch('departments', 'deleted', 'all');
    }

    public function getStatistics(): array
    {
        return [
            'total_departments' => Department::count(),
            'with_users' => Department::has('users')->count(),
            'without_users' => Department::doesntHave('users')->count(),
            'users_per_department' => Department::withCount('users')
                ->orderByDesc('users_count')
                ->get()
                ->map(fn($dept) => [
                    'id' => $dept->id_department,
                    'name' => $dept->department_name,
                    'user_count' => $dept->users_count
                ])
        ];
    }
}
