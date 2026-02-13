<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;
use App\Http\Requests\Department\{StoreDepartmentRequest, UpdateDepartmentRequest};
use Illuminate\Http\{Request, JsonResponse};

class DepartmentController extends Controller
{
    public function __construct(private DepartmentService $departmentService)
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
        $departments = $this->departmentService->getPaginated($filters, $perPage);
        return $this->success($departments);
    }

    public function all(): JsonResponse
    {
        $departments = $this->departmentService->getAll();
        return $this->success($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        try {
            $department = $this->departmentService->create($request->validated());
            return $this->success($department, 'Departemen berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $department = $this->departmentService->find($id);
        if (!$department) {
            return $this->notFound('Departemen tidak ditemukan.');
        }
        $data = [
            'department' => $department,
            'users' => $this->departmentService->findWithUsers($id),
            'users_count' => $department->users()->count(),
        ];
        return $this->success($data);
    }

    public function update(UpdateDepartmentRequest $request, string $id): JsonResponse
    {
        $department = $this->departmentService->find($id);
        if (!$department) {
            return $this->notFound('Departemen tidak ditemukan.');
        }
        try {
            $updated = $this->departmentService->update($department, $request->validated());
            return $this->success($updated, 'Departemen berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $department = $this->departmentService->find($id);
        if (!$department) {
            return $this->notFound('Departemen tidak ditemukan.');
        }
        try {
            $this->departmentService->delete($request->all() ,$department);
            return $this->success([], 'Departemen berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->departmentService->getStatistics();
        return $this->success($stats);
    }
}
