<?php

namespace App\Http\Controllers;

use App\Services\LoanService;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\UpdateLoanRequest;
use Illuminate\Http\{Request, JsonResponse};

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'status' => $request->get('status'),
            'department' => $request->get('department'),
            'job_profile' => $request->get('job_profile'),
            'asset_location' => $request->get('asset_location'),
            'asset_category' => $request->get('asset_category'),
            'search' => $request->get('search'),
            'borrow_date' => $request->get('borrow_date'),
            'borrow_date_start' => $request->get('borrow_date_start'),
            'borrow_date_end' => $request->get('borrow_date_end'),
            'due_date' => $request->get('due_date'),
            'due_date_start' => $request->get('due_date_start'),
            'due_date_end' => $request->get('due_date_end'),
            'return_date' => $request->get('return_date'),
            'return_date_start' => $request->get('return_date_start'),
            'return_date_end' => $request->get('return_date_end'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $loans = $this->loanService->getPaginated($filters, $perPage);
        return $this->success($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        try {
            $loan = $this->loanService->create($request->validated());
            return $this->success($loan, 'Permintaan Peminjaman berhasil dibuat.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        return $this->success($loan);
    }

    public function update(UpdateLoanRequest $request, string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $updatedLoan = $this->loanService->update($loan, $request->validated());
            return $this->success($updatedLoan, 'Peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function approve(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $approvedLoan = $this->loanService->approve($loan);
            return $this->success($approvedLoan, 'Peminjaman berhasil disetujui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function reject(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $rejectedLoan = $this->loanService->reject($loan);
            return $this->success($rejectedLoan, 'Peminjaman berhasil ditolak.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function requestReturn(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $updatedLoan = $this->loanService->requestReturn($loan);
            return $this->success($updatedLoan, 'Permintaan pengembalian berhasil dibuat.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function confirmReturn(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $returnedLoan = $this->loanService->confirmReturn($loan);
            return $this->success($returnedLoan, 'Pengembalian berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $this->loanService->cancel($loan);
            return $this->success([], 'Peminjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $loan = $this->loanService->find($id);
        if (!$loan) {
            return $this->notFound('Peminjaman tidak ditemukan.');
        }
        try {
            $this->loanService->delete($request->all(), $loan);
            return $this->success([], 'Peminjaman berhasil dihapus.');
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
            'department' => $request->get('department'),
            'job_profile' => $request->get('job_profile'),
            'asset_location' => $request->get('asset_location'),
            'asset_category' => $request->get('asset_category'),
            'borrow_date' => $request->get('borrow_date'),
            'borrow_date_start' => $request->get('borrow_date_start'),
            'borrow_date_end' => $request->get('borrow_date_end'),
            'due_date' => $request->get('due_date'),
            'due_date_start' => $request->get('due_date_start'),
            'due_date_end' => $request->get('due_date_end'),
            'return_date' => $request->get('return_date'),
            'return_date_start' => $request->get('return_date_start'),
            'return_date_end' => $request->get('return_date_end'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $history = $this->loanService->getPaginated($filters, $perPage);
        return $this->success($history);
    }

    public function userHistory(string $userId): JsonResponse
    {
        $history = $this->loanService->getUserHistory($userId);
        return $this->success($history);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->loanService->getStatistics();
        return $this->success($stats);
    }
}
