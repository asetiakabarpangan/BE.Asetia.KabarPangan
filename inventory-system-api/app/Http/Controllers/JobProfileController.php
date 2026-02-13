<?php

namespace App\Http\Controllers;

use App\Services\JobProfileService;
use App\Http\Requests\JobProfile\{StoreJobProfileRequest, UpdateJobProfileRequest};
use Illuminate\Http\{JsonResponse, Request};

class JobProfileController extends Controller
{
    public function __construct(private JobProfileService $jobProfileService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search'  => $request->get('search'),
            'sortBy'  => $request->get('sort_by'),
            'sortDir' => $request->get('sort_dir'),
        ];
        $jobProfiles = $this->jobProfileService->getPaginated($filters, $perPage);
        return $this->success($jobProfiles);
    }

    public function all(): JsonResponse
    {
        $jobProfiles = $this->jobProfileService->getAll();
        return $this->success($jobProfiles);
    }

    public function store(StoreJobProfileRequest $request): JsonResponse
    {
        try {
            $jobProfile = $this->jobProfileService->create($request->validated());
            return $this->success($jobProfile, 'Profil pekerjaan berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $jobProfile = $this->jobProfileService->find($id);
        if (!$jobProfile) {
            return $this->notFound('Profil pekerjaan tidak ditemukan.');
        }
        $data = [
            'job_profile' => $jobProfile,
            'users' => $this->jobProfileService->findWithUsers($id),
            'recommendations' => $this->jobProfileService->findWithRecommendations($id),
            'procurements' => $this->jobProfileService->findWithProcurements($id),
            'users_count' => $jobProfile->users()->count(),
            'recommendations_count' => $jobProfile->recommendations()->count(),
            'procurements_count' => $jobProfile->procurements()->count(),
        ];
        return $this->success($data);
    }

    public function update(UpdateJobProfileRequest $request, string $id): JsonResponse
    {
        $jobProfile = $this->jobProfileService->find($id);
        if (!$jobProfile) {
            return $this->notFound('Profil pekerjaan tidak ditemukan.');
        }
        try {
            $jobProfile = $this->jobProfileService->update($jobProfile, $request->validated());
            return $this->success($jobProfile, 'Profil pekerjaan berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $jobProfile = $this->jobProfileService->find($id);
        if (!$jobProfile) {
            return $this->notFound('Profil pekerjaan tidak ditemukan.');
        }
        try {
            $this->jobProfileService->delete($request->all(), $jobProfile);
            return $this->success([], 'Profil pekerjaan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->jobProfileService->getStatistics();
        return $this->success($stats);
    }

    public function getRecommendedAssets(string $id): JsonResponse
    {
        $jobProfile = $this->jobProfileService->find($id);
        if (!$jobProfile) {
            return $this->notFound('Profil pekerjaan tidak ditemukan.');
        }
        try {
            $data = $this->jobProfileService->getRecommendedAssets($jobProfile);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
