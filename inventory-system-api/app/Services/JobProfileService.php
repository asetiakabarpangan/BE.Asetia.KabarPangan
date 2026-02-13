<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\JobProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class JobProfileService
{
    public function getAll(): Collection
    {
        return JobProfile::select('id_job_profile', 'profile_name')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = JobProfile::query();
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('profile_name', 'ilike', "%{$search}%")
                ->orWhere('id_job_profile', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%");
            });
        }
        $allowSortBy = ['id_job_profile', 'profile_name', 'description', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function find(string $id): ?JobProfile
    {
        return JobProfile::find($id);
    }

    public function findWithUsers($id)
    {
        return JobProfile::find($id)?->users()
            ->select('id_user', 'name', 'email', 'position', 'role')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function findWithRecommendations($id)
    {
        return JobProfile::find($id)?->recommendations()
            ->select('id_asset')
            ->with('asset:asset_name,brand,condition,availability_status')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function findWithProcurements($id)
    {
        return JobProfile::find($id)?->procurements()
            ->select('id_procurement', 'item_name')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function create(array $data): JobProfile
    {
        $jobProfile = DB::transaction(function () use ($data) {
            $jobProfile = JobProfile::create($data);
            return $jobProfile->fresh();
        });
        DataChanged::dispatch('job_profiles', 'created', 'all');
        return $jobProfile;
    }

    public function update(JobProfile $jobProfile, array $data): JobProfile
    {
        $updatedJobProfile = DB::transaction(function () use ($jobProfile, $data) {
            $jobProfile->update($data);
            return $jobProfile->fresh();
        });
        DataChanged::dispatch('job_profiles', 'updated', 'all');
        return $updatedJobProfile;
    }

    public function delete(array $request, JobProfile $jobProfile): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusAset321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if ($jobProfile->users()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus profil pekerjaan yang masih memiliki pengguna.');
        }
        if ($jobProfile->recommendations()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus profil pekerjaan yang masih memiliki rekomendasi aset.');
        }
        if ($jobProfile->procurements()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus profil pekerjaan yang masih memiliki pengajuan procurement.');
        }
        DB::transaction(function () use ($jobProfile) {
            $jobProfile->delete();
        });
        DataChanged::dispatch('job_profiles', 'deleted', 'all');
    }

    public function getStatistics(): array
    {
        return [
            'total_job_profiles' => JobProfile::count(),
            'with_users' => JobProfile::has('users')->count(),
            'with_recommendations' => JobProfile::has('recommendations')->count(),
            'with_procurements' => JobProfile::has('procurements')->count(),
            'users_per_profile' => JobProfile::withCount('users')
                ->orderByDesc('users_count')
                ->get()
                ->map(fn($profile) => [
                    'id' => $profile->id_job_profile,
                    'name' => $profile->profile_name,
                    'user_count' => $profile->users_count
                ]),
            'recommendations_per_profile' => JobProfile::withCount('recommendations')
                ->orderByDesc('recommendations_count')
                ->get()
                ->map(fn($profile) => [
                    'id' => $profile->id_job_profile,
                    'name' => $profile->profile_name,
                    'recommendation_count' => $profile->recommendations_count
                ]),
            'procurements_per_profile' => JobProfile::withCount('procurements')
                ->orderByDesc('procurements_count')
                ->get()
                ->map(fn($profile) => [
                    'id' => $profile->id_job_profile,
                    'name' => $profile->profile_name,
                    'procurement_count' => $profile->procurements_count
                ])
        ];
    }

    public function getRecommendedAssets(JobProfile $jobProfile): array
    {
        $recommendations = $jobProfile->recommendations()
            ->with(['asset.category', 'asset.location', 'asset.images'])
            ->whereHas('asset', fn($q) =>
                $q->where('availability_status', 'Tersedia')
                  ->where('condition', 'Baik')
            )
            ->get();
        return [
            'job_profile' => $jobProfile,
            'available_assets' => $recommendations
        ];
    }
}
