<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\{JsonResponse, Request};

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'department' => $request->get('id_department'),
            'job_profile' => $request->get('id_job_profile'),
            'role' => $request->get('role'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $users = $this->userService->getPaginated($filters, $perPage);
        return $this->success($users);
    }

    public function getUserFilters(Request $request): JsonResponse
    {
        $filters = [
            'role' => $request->get('role'),
            'department' => $request->get('id_department'),
            'job_profile' => $request->get('id_job_profile'),
        ];
        $users = $this->userService->getUserFilters($filters);
        return $this->success($users);
    }

    public function all(): JsonResponse
    {
        $users = $this->userService->getAll();
        return $this->success($users);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->userService->find($id);
        if (!$user) {
            return $this->notFound('Pengguna tidak ditemukan.');
        }
        $data = [
            'user' => $user,
            'loans' => $this->userService->findWithLoans($id),
            'maintenances' => $this->userService->findWithMaintenancesOfficer($id),
            'procurementsRequester' => $this->userService->findWithProcurementsRequester($id),
            'procurementsApprover' => $this->userService->findWithProcurementsApprover($id),
            'loans_count' => $user->loans()->count(),
            'maintenances_count' => $user->maintenancesOfficer()->count(),
            'procurements_requester_count' => $user->procurementsRequester()->count(),
            'procurements_approver_count' => $user->procurementsApprover()->count(),
        ];
        return $this->success($data);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->userService->find($id);
        if (!$user) {
            return $this->notFound('Pengguna tidak ditemukan.');
        }
        try {
            $this->userService->delete($request->all(), $user);
            return $this->success([], 'Pengguna berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->userService->getStatistics();
        return $this->success($stats);
    }
}
