<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\{Recommendation, JobProfile, User};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class RecommendationService
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Recommendation::with(['asset', 'jobProfile']);
        $jobProfileId = $filters['job_profile_id'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($jobProfileId) {
            $query->where('id_job_profile', $jobProfileId);
        }
        $allowSortBy = ['id_asset', 'id_job_profile', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function create(array $data): Recommendation
    {
        $exists = Recommendation::where('id_asset', $data['id_asset'])
            ->where('id_job_profile', $data['id_job_profile'])
            ->exists();
        if ($exists) {
            throw new Exception('Rekomendasi untuk aset dan profil ini sudah ada.');
        }
        $recommendation = Recommendation::create([
            'id_asset'       => $data['id_asset'],
            'id_job_profile' => $data['id_job_profile'],
        ]);
        DataChanged::dispatch('recommendations', 'created', 'all');
        return $recommendation->load(['asset', 'jobProfile']);
    }

    public function bulkInsert(array $data): array
    {
        $assetId = $data['id_asset'];
        $jobProfileIds = array_unique($data['job_profile_ids'] ?? []);
        $bulkInsert = DB::transaction(function () use ($assetId, $jobProfileIds) {
            $existingIds = Recommendation::where('id_asset', $assetId)
                ->pluck('id_job_profile')
                ->toArray();
            $toDelete = array_diff($existingIds, $jobProfileIds);
            $toInsert = array_diff($jobProfileIds, $existingIds);
            if ($toDelete) {
                Recommendation::where('id_asset', $assetId)
                    ->whereIn('id_job_profile', $toDelete)
                    ->delete();
            }
            if ($toInsert) {
                Recommendation::insert(
                    array_map(fn($id) => [
                        'id_asset' => $assetId,
                        'id_job_profile' => $id,
                    ], $toInsert)
                );
            }
            return [
                'id_asset' => $assetId,
                'added' => array_values($toInsert),
                'removed' => array_values($toDelete),
                'total_current' => count($jobProfileIds),
            ];
        });
        DataChanged::dispatch('recommendations', 'created', 'all');
        return $bulkInsert;
    }

    public function delete(string $assetId, string $jobProfileId): void
    {
        $deleted = Recommendation::where('id_asset', $assetId)
            ->where('id_job_profile', $jobProfileId)
            ->delete();
        if ($deleted === 0) {
            throw new Exception('Rekomendasi tidak ditemukan.');
        }
        DataChanged::dispatch('recommendations', 'deleted', 'all');
    }

    public function getByJobProfile(string $jobProfileId): array
    {
        $jobProfile = JobProfile::find($jobProfileId);
        if (!$jobProfile) {
            throw new Exception('Profil pekerjaan tidak ditemukan.');
        }
        $recommendations = Recommendation::with(['asset.category', 'asset.location', 'asset.images'])
            ->where('id_job_profile', $jobProfileId)
            ->get();
        return [
            'job_profile'        => $jobProfile,
            'recommended_assets' => $recommendations
        ];
    }

    public function getAvailableForUser(string $userId): array
    {
        $user = User::with('jobProfile')->find($userId);
        if (!$user) {
            throw new Exception('Pengguna tidak ditemukan.');
        }
        if (!$user->id_job_profile) {
            throw new Exception('Pengguna tidak memiliki profil pekerjaan.');
        }
        $recommendedAssets = Recommendation::with(['asset.category', 'asset.location', 'asset.images'])
            ->where('id_job_profile', $user->id_job_profile)
            ->whereHas('asset', function ($query) {
                $query->where('availability_status', 'Tersedia')
                      ->where('condition', 'Baik');
            })
            ->get();
        return [
            'user' => [
                'id'          => $user->id_user,
                'name'        => $user->name,
                'position'    => $user->position,
                'job_profile' => $user->jobProfile
            ],
            'recommended_assets' => $recommendedAssets
        ];
    }

    public function getStatistics(): array
    {
        $total = Recommendation::count();
        $byJobProfile = Recommendation::selectRaw('id_job_profile, COUNT(*) as count')
            ->with('jobProfile:id_job_profile,profile_name')
            ->groupBy('id_job_profile')
            ->get();
        $byCategory = Recommendation::join('assets', 'recommendations.id_asset', '=', 'assets.id_asset')
            ->join('categories', 'assets.id_category', '=', 'categories.id_category')
            ->selectRaw('categories.id_category, categories.category_name, COUNT(*) as count')
            ->groupBy('categories.id_category', 'categories.category_name')
            ->get();
        $mostRecommended = Recommendation::selectRaw('id_asset, COUNT(*) as recommendation_count')
            ->with('asset:id_asset,asset_name,brand')
            ->groupBy('id_asset')
            ->orderByDesc('recommendation_count')
            ->limit(10)
            ->get();
        return [
            'total_recommendations'   => $total,
            'by_job_profile'          => $byJobProfile,
            'by_asset_category'       => $byCategory,
            'most_recommended_assets' => $mostRecommended,
        ];
    }
}
