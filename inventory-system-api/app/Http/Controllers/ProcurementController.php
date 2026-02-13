<?php

namespace App\Http\Controllers;

use App\Services\ProcurementService;
use App\Http\Requests\Procurement\{StoreProcurementRequest, UpdateProcurementRequest};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Auth;

class ProcurementController extends Controller
{
    public function __construct(private ProcurementService $procurementService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'department_requester' => $request->get('department_requester'),
            'job_profile_target' => $request->get('job_profile_target'),
            'quantity' => $request->get('quantity'),
            'request_date' => $request->get('request_date'),
            'request_date_start' => $request->get('request_date_start'),
            'request_date_end' => $request->get('request_date_end'),
            'action_date' => $request->get('action_date'),
            'action_date_start' => $request->get('action_date_start'),
            'action_date_end' => $request->get('action_date_end'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $procurements = $this->procurementService->getPaginated($filters, $perPage);
        return $this->success($procurements);
    }

    public function store(StoreProcurementRequest $request): JsonResponse
    {
        try {
            $procurement = $this->procurementService->create($request->validated());
            return $this->success($procurement, 'Pengajuan pengadaan berhasil dibuat.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        return $this->success($procurement);
    }

    public function update(UpdateProcurementRequest $request, string $id): JsonResponse
    {
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $updatedProcurement = $this->procurementService->update($procurement, $request->validated());
            return $this->success($updatedProcurement, 'Pengajuan pengadaan berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $request->merge(['id_approver' => $user->id_user]);
        $request->validate([
            'approver_notes' => 'nullable|string',
        ]);
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $approved = $this->procurementService->approve(
                $procurement,
                $request->id_approver,
                $request->approver_notes
            );
            return $this->success($approved, 'Pengajuan pengadaan berhasil disetujui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $request->merge(['id_approver' => $user->id_user]);
        $request->validate([
            'approver_notes' => 'required|string',
        ]);
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $rejected = $this->procurementService->reject(
                $procurement,
                $request->id_approver,
                $request->approver_notes
            );
            return $this->success($rejected, 'Pengajuan pengadaan berhasil ditolak.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function complete(string $id): JsonResponse
    {
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $completed = $this->procurementService->complete($procurement);
            return $this->success($completed, 'Pengajuan pengadaan berhasil diselesaikan.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $this->procurementService->cancel($procurement);
            return $this->success([], 'Pengajuan pengadaan berhasil dibatalkan.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $procurement = $this->procurementService->find($id);
        if (!$procurement) {
            return $this->notFound('Pengajuan pengadaan tidak ditemukan.');
        }
        try {
            $this->procurementService->delete($procurement);
            return $this->success([], 'Pengajuan pengadaan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function myHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'user' => $user->id_user,
            'status' => $request->get('status'),
            'department_requester' => $request->get('department_requester'),
            'job_profile_target' => $request->get('job_profile_target'),
            'quantity' => $request->get('quantity'),
            'request_date' => $request->get('request_date'),
            'request_date_start' => $request->get('request_date_start'),
            'request_date_end' => $request->get('request_date_end'),
            'action_date' => $request->get('action_date'),
            'action_date_start' => $request->get('action_date_start'),
            'action_date_end' => $request->get('action_date_end'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $history = $this->procurementService->getPaginated($filters, $perPage);
        return $this->success($history);
    }

    public function userHistory(string $userId): JsonResponse
    {
        $history = $this->procurementService->getUserHistory($userId);
        return $this->success($history);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->procurementService->getStatistics();
        return $this->success($stats);
    }
}
