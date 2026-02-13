<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\{Loan, Asset};
use App\Helpers\IdGenerator;
use App\Notifications\{LoanStatusUpdated, NewRequestNotification};
use App\Traits\{NotifiesManagement, ValidatesLocationAccess};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\{DB, Auth, Hash};
use Exception;
use Illuminate\Auth\Access\AuthorizationException;

class LoanService
{
    use ValidatesLocationAccess;
    use NotifiesManagement;

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Loan::select('loans.*')->with(['asset.category', 'asset.location', 'user']);
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $user = $filters['user'] ?? null;
        $department = $filters['department'] ?? null;
        $jobProfile = $filters['job_profile'] ?? null;
        $assetLocation = $filters['asset_location'] ?? null;
        $assetCategory = $filters['asset_category'] ?? null;
        $borrowDate = $filters['borrow_date'] ?? null;
        $borrowDateStart = $filters['borrow_date_start'] ?? null;
        $borrowDateEnd = $filters['borrow_date_end'] ?? null;
        $dueDate = $filters['due_date'] ?? null;
        $dueDateStart = $filters['due_date_start'] ?? null;
        $dueDateEnd = $filters['due_date_end'] ?? null;
        $returnDate = $filters['return_date'] ?? null;
        $returnDateStart = $filters['return_date_start'] ?? null;
        $returnDateEnd = $filters['return_date_end'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($user) {
            $query->where('id_user', $user);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id_loan', 'ilike', "%{$search}%")
                ->orWhere('id_asset', 'ilike', "%{$search}%")
                ->orWhere('id_user', 'ilike', "%{$search}%")
                ->orWhereHas('asset', function($q2) use ($search) {
                    $q2->where('asset_name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('user', function($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                });
            });
        }
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('loan_status', $status);
            } else {
                $query->where('loan_status', $status);
            }
        }
        if ($department) {
            $query->whereHas('user', function($q) use ($department) {
                $q->where('id_department', $department);
            });
        }
        if ($jobProfile) {
            $query->whereHas('user', function($q) use ($jobProfile) {
                $q->where('id_job_profile', $jobProfile);
            });
        }
        if ($assetLocation) {
            $query->whereHas('asset', function($q) use ($assetLocation) {
                $q->where('id_location', $assetLocation);
            });
        }
        if ($assetCategory) {
            $query->whereHas('asset', function($q) use ($assetCategory) {
                $q->where('id_category', $assetCategory);
            });
        }
        if ($borrowDateStart || $borrowDateEnd) {
            if ($borrowDateStart) {
                $query->whereDate('borrow_date', '>=', $borrowDateStart);
            }
            if ($borrowDateEnd) {
                $query->whereDate('borrow_date', '<=', $borrowDateEnd);
            }
        } elseif ($borrowDate) {
            $query->whereDate('borrow_date', $borrowDate);
        }
        if ($dueDateStart || $dueDateEnd) {
            if ($dueDateStart) {
                $query->whereDate('due_date', '>=', $dueDateStart);
            }
            if ($dueDateEnd) {
                $query->whereDate('due_date', '<=', $dueDateEnd);
            }
        } elseif ($dueDate) {
            $query->whereDate('due_date', $dueDate);
        }
        if ($returnDateStart || $returnDateEnd) {
            if ($returnDateStart) {
                $query->whereDate('return_date', '>=', $returnDateStart);
            }
            if ($returnDateEnd) {
                $query->whereDate('return_date', '<=', $returnDateEnd);
            }
        } elseif ($returnDate) {
            $query->whereDate('return_date', $returnDate);
        }
        $allowSortBy = ['id_loan', 'id_asset', 'id_user', 'loan_status', 'borrow_date', 'due_date', 'return_date', 'created_at', 'updated_at', 'asset_name', 'user_name'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'asset_name') {
            $query->join('assets', 'loans.id_asset', '=', 'assets.id_asset')
                  ->orderBy('assets.asset_name', $sortDir);
        }
        elseif ($sortBy === 'user_name') {
            $query->join('users', 'loans.id_user', '=', 'users.id_user')
                  ->orderBy('users.name', $sortDir);
        }
        else {
            $query->orderBy('loans.' . $sortBy, $sortDir);
        }
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Loan
    {
        $authUser = Auth::user();
        $query = Loan::with([
            'asset.category',
            'asset.location',
            'user',
            'user.department',
            'user.jobProfile'
        ])->where('id_loan', $id);
        if (!in_array($authUser->role, ['admin', 'moderator']) && $query->first()->id_user !== $authUser->id_user) {
            throw new AuthorizationException();
        }
        return $query->first();
    }

    public function create(array $data): Loan
    {
        $asset = Asset::find($data['id_asset']);
        if ($asset->availability_status !== 'Tersedia') {
            throw new Exception('Aset tidak tersedia untuk dipinjam (Status: ' . $asset->availability_status . ').');
        }
        $loan = DB::transaction(function () use ($data, $asset) {
            $loanId = IdGenerator::generateUniqueLoanId();
            $loanData = array_merge($data, [
                'id_loan'     => $loanId,
                'loan_status' => 'Menunggu Konfirmasi Peminjaman',
            ]);
            $loan = Loan::create($loanData);
            $this->notifyRelevantUsers(
                new NewRequestNotification($loan, 'loan'),
                $asset->id_location
            );
            return $loan->load(['asset.category', 'user']);
        });
        DataChanged::dispatch('loans', 'create', 'admin', (string) $loan->id_user);
        return $loan;
    }

    public function update(Loan $loan, array $data): Loan
    {
        $user = Auth::user();
        if (in_array($user->role, ['admin', 'moderator'])) {
            $this->ensureUserCanManageLocation($loan->asset->id_location);
            $updateLoan = DB::transaction(function () use ($loan, $data) {
                $loan->update($data);
                return $loan->fresh(['asset.category', 'user']);
            });
        } else if ($user->role === 'employee' && $user->id_user === $loan->id_user) {
            if ($loan->loan_status !== 'Menunggu Konfirmasi Peminjaman') {
                throw new Exception('Tidak dapat mengubah peminjaman yang sudah terkonfirmasi.');
            }
            $allowedFields = [
                'id_asset',
                'borrow_date',
                'due_date',
            ];
            $filteredData = collect($data)
                ->only($allowedFields)
                ->toArray();
            $updateLoan = DB::transaction(function () use ($loan, $filteredData) {
                $loan->update($filteredData);
                return $loan->fresh(['asset.category', 'user']);
            });
        } else {
            throw new Exception('Akses tidak diizinkan.', 403);
        }
        DataChanged::dispatch('loans', 'updated', 'admin', (string) $loan->id_user);
        return $updateLoan;
    }

    public function approve(Loan $loan): Loan
    {
        $this->ensureUserCanManageLocation($loan->asset->id_location);
        if ($loan->loan_status !== 'Menunggu Konfirmasi Peminjaman') {
            throw new Exception('Peminjaman tidak dalam status menunggu konfirmasi.');
        }
        $approveLoan = DB::transaction(function () use ($loan) {
            $loan->update([
                'loan_status' => 'Dipinjam'
            ]);
            $loan->asset()->update([
                'availability_status' => 'Dipinjam'
            ]);
            Loan::where('id_asset', $loan->id_asset)
                ->where('id_loan', '!=', $loan->id_loan)
                ->where('loan_status', 'Menunggu Konfirmasi Peminjaman')
                ->where('borrow_date', '<', $loan->due_date)
                ->update(['loan_status' => 'Peminjaman Ditolak']);
            $loan->user->notify(new LoanStatusUpdated($loan, Auth::user()));
            return $loan->fresh(['asset.category', 'user']);
        });
        DataChanged::dispatch('loans', 'updated', 'admin', (string) $loan->id_user);
        return $approveLoan;
    }

    public function reject(Loan $loan): Loan
    {
        $this->ensureUserCanManageLocation($loan->asset->id_location);
        if ($loan->loan_status !== 'Menunggu Konfirmasi Peminjaman') {
            throw new Exception('Peminjaman tidak dalam status menunggu konfirmasi.');
        }
        $loan->update([
            'loan_status' => 'Peminjaman Ditolak'
        ]);
        $loan->user->notify(new LoanStatusUpdated($loan, Auth::user()));
        DataChanged::dispatch('loans', 'updated', 'admin', (string) $loan->id_user);
        return $loan->fresh(['asset.category', 'user']);
    }

    public function requestReturn(Loan $loan): Loan
    {
        $user = Auth::user();
        if ($user->id_user !== $loan->id_user) {
            $this->ensureUserCanManageLocation($loan->asset->id_location);
        }
        if ($loan->loan_status !== 'Dipinjam') {
            throw new Exception('Peminjaman tidak dalam status dipinjam.');
        }
        $loan->update([
            'loan_status' => 'Menunggu Konfirmasi Pengembalian',
            'return_date' => now()
        ]);
        $this->notifyRelevantUsers(
            new NewRequestNotification($loan, 'loan'),
            $loan->asset->id_location
        );
        DataChanged::dispatch('loans', 'updated', 'admin', (string) $loan->id_user);
        return $loan->fresh(['asset.category', 'user']);
    }

    public function confirmReturn(Loan $loan): Loan
    {
        $this->ensureUserCanManageLocation($loan->asset->id_location);
        if ($loan->loan_status !== 'Menunggu Konfirmasi Pengembalian') {
            throw new Exception('Peminjaman tidak dalam status menunggu konfirmasi pengembalian.');
        }
        $confirmReturn = DB::transaction(function () use ($loan) {
            $loan->update([
                'loan_status' => 'Dikembalikan',
                'return_date' => now()
            ]);
            $loan->asset()->update([
                'availability_status' => 'Tersedia'
            ]);
            $loan->user->notify(new LoanStatusUpdated($loan, Auth::user()));
            return $loan->fresh(['asset.category', 'user']);
        });
        DataChanged::dispatch('loans', 'updated', 'admin', (string) $loan->id_user);
        return $confirmReturn;
    }

    public function cancel(Loan $loan): void
    {
        $user = Auth::user();
        if ($user->id_user !== $loan->id_user) {
            $this->ensureUserCanManageLocation($loan->asset->id_location);
        }
        if ($loan->loan_status !== 'Menunggu Konfirmasi Peminjaman') {
            throw new Exception('Hanya peminjaman yang menunggu konfirmasi yang dapat dibatalkan.');
        }
        $loan->delete();
        DataChanged::dispatch('loans', 'deleted', 'admin', (string) $user->id_user);
    }

    public function delete(array $request, Loan $loan): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusPeminjaman321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if (in_array($loan->loan_status, ['Dipinjam', 'Menunggu Konfirmasi Pengembalian'])) {
            throw new \Exception('Tidak dapat menghapus peminjaman yang masih dipinjam.');
        }
        DB::transaction(function () use ($loan) {
            $loan->delete();
        });
        DataChanged::dispatch('loans', 'deleted', 'admin', (string) $loan->id_user);
    }

    public function getUserHistory(string $userId): Collection
    {
        return Loan::with(['asset.category'])
            ->where('id_user', $userId)
            ->latest()
            ->get();
    }

    public function getStatistics(): array
    {
        $stats = Loan::toBase()
            ->selectRaw("
                COUNT(*) as total_loans,
                SUM(CASE WHEN loan_status = 'Menunggu Konfirmasi Peminjaman' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN loan_status = 'Dipinjam' THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN loan_status = 'Menunggu Konfirmasi Pengembalian' THEN 1 ELSE 0 END) as pending_return,
                SUM(CASE WHEN loan_status = 'Dikembalikan' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN loan_status = 'Dipinjam' AND due_date < ? THEN 1 ELSE 0 END) as overdue
            ", [now()])
            ->first();
        $today = Loan::whereDate('created_at', today())->count();
        $thisWeek = Loan::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = Loan::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        return [
            'total_loans' => (int) $stats->total_loans,
            'pending_approval' => (int) $stats->pending_approval,
            'active_loans' => (int) $stats->active_loans,
            'pending_return' => (int) $stats->pending_return,
            'completed' => (int) $stats->completed,
            'overdue' => (int) $stats->overdue,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
        ];
    }
}
