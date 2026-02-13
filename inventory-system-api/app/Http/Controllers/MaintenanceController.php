<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceService;
use App\Http\Requests\Maintenance\{StoreMaintenanceRequest, UpdateMaintenanceRequest};
use Illuminate\Http\{Request, JsonResponse};

class MaintenanceController extends Controller
{
    public function __construct(private MaintenanceService $maintenanceService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'location' => $request->get('location'),
            'officer_id' => $request->get('officer_id'),
            'maintenance_status' => $request->get('maintenance_status'),
            'maintenance_date' => $request->get('maintenance_date'),
            'maintenance_date_start' => $request->get('maintenance_date_start'),
            'maintenance_date_end' => $request->get('maintenance_date_end'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $maintenances = $this->maintenanceService->getPaginated($filters, $perPage);
        return $this->success($maintenances);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        try {
            $maintenance = $this->maintenanceService->create($request->validated());
            return $this->success($maintenance, 'Catatan perbaikan berhasil dibuat.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $maintenance = $this->maintenanceService->find($id);
        if (!$maintenance) {
            return $this->notFound('Catatan perbaikan tidak ditemukan.');
        }
        return $this->success($maintenance);
    }

    public function update(UpdateMaintenanceRequest $request, string $id): JsonResponse
    {
        $maintenance = $this->maintenanceService->find($id);
        if (!$maintenance) {
            return $this->notFound('Catatan perbaikan tidak ditemukan.');
        }
        try {
            $updatedMaintenance = $this->maintenanceService->update($maintenance, $request->validated());
            return $this->success($updatedMaintenance, 'Catatan perbaikan berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function complete(string $id): JsonResponse
    {
        $maintenance = $this->maintenanceService->find($id);
        if (!$maintenance) {
            return $this->notFound('Catatan perbaikan tidak ditemukan.');
        }
        try {
            $completedMaintenance = $this->maintenanceService->complete($maintenance);
            return $this->success($completedMaintenance, 'Perbaikan selesai. Aset sudah tersedia sekarang.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $maintenance = $this->maintenanceService->find($id);
        if (!$maintenance) {
            return $this->notFound('Catatan perbaikan tidak ditemukan.');
        }
        try {
            $this->maintenanceService->delete($request->all(), $maintenance);
            return $this->success([], 'Catatan perbaikan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function assetHistory(string $assetId): JsonResponse
    {
        $history = $this->maintenanceService->getAssetHistory($assetId);
        return $this->success($history);
    }

    public function officerHistory(string $officerId): JsonResponse
    {
        $history = $this->maintenanceService->getOfficerHistory($officerId);
        return $this->success($history);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->maintenanceService->getStatistics();
        return $this->success($stats);
    }
}
