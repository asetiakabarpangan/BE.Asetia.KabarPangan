<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Http\Requests\Recommendation\StoreRecommendationRequest;
use App\Http\Requests\Recommendation\BulkCreateRecommendationRequest;
use App\Http\Requests\Recommendation\BulkUpdateRecommendationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    public function __construct(private RecommendationService $recommendationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'job_profile_id' => $request->get('job_profile_id'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $recommendations = $this->recommendationService->getPaginated($filters, $perPage);
        return $this->success($recommendations);
    }

    public function store(StoreRecommendationRequest $request): JsonResponse
    {
        try {
            $recommendation = $this->recommendationService->create($request->validated());
            return $this->success($recommendation, 'Rekomendasi berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function bulkInsert(BulkUpdateRecommendationRequest $request): JsonResponse
    {
        try {
            $result = $this->recommendationService->bulkInsert(
                $request->validated()
            );
            return $this->success($result, 'List rekomendasi berhasil ditambahkan/perbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(string $assetId, string $jobProfileId): JsonResponse
    {
        try {
            $this->recommendationService->delete($assetId, $jobProfileId);
            return $this->success([], 'Rekomendasi berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function getByJobProfile(string $jobProfileId): JsonResponse
    {
        try {
            $data = $this->recommendationService->getByJobProfile($jobProfileId);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function myAvailableAssets(Request $request): JsonResponse
    {
        try {
            $data = $this->recommendationService->getAvailableForUser($request->user()->id_user);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function getAvailableForUser(string $userId): JsonResponse
    {
        try {
            $data = $this->recommendationService->getAvailableForUser($userId);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->recommendationService->getStatistics();
        return $this->success($stats);
    }
}
