<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\{StoreLocationRequest, UpdateLocationRequest};
use App\Services\LocationService;
use Illuminate\Http\{Request, JsonResponse};

class LocationController extends Controller
{
    public function __construct(private LocationService $locationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'personInCharge' => $request->get('id_person_in_charge'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $locations = $this->locationService->getPaginated($filters, $perPage);
        return $this->success($locations);
    }

    public function all(): JsonResponse
    {
        $locations = $this->locationService->getAll();
        return $this->success($locations);
    }

    public function suggestId(Request $request): JsonResponse
    {
        $request->validate(['building' => 'required|string']);
        $suggestion = $this->locationService->suggestId($request->building);
        return $this->success($suggestion);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        try {
            $location = $this->locationService->create($request->validated());
            return $this->success($location, 'Lokasi berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $location = $this->locationService->find($id);
        if (!$location) {
            return $this->notFound('Lokasi tidak ditemukan.');
        }
        $data = [
            'location' => $location,
            'person_in_charge' => $this->locationService->findWithPersonInCharge($id),
            'assets' => $this->locationService->findWithAssets($id),
            'assets_count' => $location->assets()->count(),
        ];
        return $this->success($data);
    }

    public function update(UpdateLocationRequest $request, string $id): JsonResponse
    {
        $location = $this->locationService->find($id);
        if (!$location) {
            return $this->notFound('Lokasi tidak ditemukan.');
        }
        try {
            $updated = $this->locationService->update($location, $request->validated());
            return $this->success($updated, 'Lokasi berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $location = $this->locationService->find($id);
        if (!$location) {
            return $this->notFound('Lokasi tidak ditemukan.');
        }
        try {
            $this->locationService->delete($request->all(), $location);
            return $this->success([], 'Lokasi berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
