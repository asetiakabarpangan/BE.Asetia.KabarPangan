<?php

namespace App\Http\Controllers;

use App\Services\AssetService;
use App\Http\Requests\Asset\{StoreAssetRequest, UpdateAssetRequest};
use Illuminate\Http\{Request, JsonResponse};

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'job_profile' => null,
            'category' => $request->get('category'),
            'location' => $request->get('location'),
            'condition' => $request->get('condition'),
            'availability' => $request->get('availability_status'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        if ($request->has('rekomendasi') && $request->rekomendasi !== 'false') {
            $filters['job_profile'] = $user->id_job_profile;
        }
        $assets = $this->assetService->getPaginated($filters, $perPage);
        return $this->success($assets);
    }

    public function all(): JsonResponse
    {
        $assets = $this->assetService->getAll();
        return $this->success($assets);
    }

    public function getAssetFilters(Request $request): JsonResponse
    {
        $filters = [
            'location' => $request->get('location'),
        ];
        $assets = $this->assetService->getAssetFilters($filters);
        return $this->success($assets);
    }

    public function searchBySpec(Request $request): JsonResponse
    {
        $request->validate([
            'specification' => 'required|array',
        ]);
        $perPage = $request->get('per_page', 10);
        $assets = $this->assetService->filterBySpecifications($request->specification, $perPage);
        return $this->success($assets);
    }

    public function suggestId(Request $request): JsonResponse
    {
        $request->validate(['id_category' => 'required|exists:categories,id_category']);
        $suggestion = $this->assetService->suggestId($request->id_category);
        return $this->success($suggestion);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        try {
            $asset = $this->assetService->create(
                $request->validated(),
                $request->file('images')
            );
            return $this->success($asset, 'Aset berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $asset = $this->assetService->findDetail($id);
        if (!$asset) {
            return $this->notFound('Aset tidak ditemukan.');
        }
        return $this->success($asset);
    }

    public function update(UpdateAssetRequest $request, string $id): JsonResponse
    {
        $asset = $this->assetService->find($id);
        if (!$asset) {
            return $this->notFound('Aset tidak ditemukan.');
        }
        try {
            $data = $request->validated();
            $data['deleted_images'] = $request->input('deleted_images', []);
            $data['existing_images'] = $request->input('existing_images', []);
            $updatedAsset = $this->assetService->update(
                $asset,
                $data,
                $request->file('images')
            );
            return $this->success($updatedAsset, 'Aset berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $asset = $this->assetService->find($id);
        if (!$asset) {
            return $this->notFound('Aset tidak ditemukan.');
        }
        try {
            $this->assetService->delete($request->all(), $asset);
            return $this->success([], 'Aset berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->assetService->getStatistics();
        return $this->success($stats);
    }
}
