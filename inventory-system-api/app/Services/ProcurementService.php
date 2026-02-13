<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\{Procurement, User};
use App\Helpers\IdGenerator;
use App\Notifications\{NewRequestNotification, ProcurementStatusUpdated};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\{DB, Notification, Auth};
use Exception;
use Illuminate\Auth\Access\AuthorizationException;

class ProcurementService
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Procurement::select('procurements.*')->with(['requester', 'requester.department', 'approver', 'approver.department', 'jobProfileTarget']);
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $user = $filters['user'] ?? null;
        $jobProfileTarget = $filters['job_profile_target'] ?? null;
        $departmentRequester = $filters['department_requester'] ?? null;
        $quantity = $filters['quantity'] ?? null;
        $requestDate = $filters['request_date'] ?? null;
        $requestDateStart = $filters['request_date_start'] ?? null;
        $requestDateEnd = $filters['request_date_end'] ?? null;
        $actionDate = $filters['action_date'] ?? null;
        $actionDateStart = $filters['action_date_start'] ?? null;
        $actionDateEnd = $filters['action_date_end'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($user) {
            $query->where('id_requester', $user);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id_procurement', 'ilike', "%{$search}%")
                ->orWhere('item_name', 'ilike', "%{$search}%")
                ->orWhereHas('requester', function($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('approver', function($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                });
            });
        }
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('procurement_status', $status);
            } else {
                $query->where('procurement_status', $status);
            }
        }
        if ($jobProfileTarget) {
            $query->where('id_job_profile_target', $jobProfileTarget);
        }
        if ($departmentRequester) {
            $query->whereHas('requester', function($q) use ($departmentRequester) {
                $q->where('id_department', $departmentRequester);
            });
        }
        if ($quantity) {
            $query->where('quantity', $quantity);
        }
        if ($requestDate) {
            $query->where('request_date', $requestDate);
        }
        if ($actionDate) {
            $query->where('action_date', $actionDate);
        }
        if ($requestDateStart || $requestDateEnd) {
            if ($requestDateStart) {
                $query->whereDate('request_date', '>=', $requestDateStart);
            }
            if ($requestDateEnd) {
                $query->whereDate('request_date', '<=', $requestDateEnd);
            }
        } elseif ($requestDate) {
            $query->whereDate('request_date', $requestDate);
        }
        if ($actionDateStart || $actionDateEnd) {
            if ($actionDateStart) {
                $query->whereDate('action_date', '>=', $actionDateStart);
            }
            if ($actionDateEnd) {
                $query->whereDate('action_date', '<=', $actionDateEnd);
            }
        } elseif ($actionDate) {
            $query->whereDate('action_date', $actionDate);
        }
        $allowSortBy = ['id_procurement', 'id_requester', 'id_approver', 'id_job_profile_target', 'item_name', 'procurement_status', 'quantity',
            'request_date', 'action_date', 'created_at', 'updated_at', 'requester_name', 'approver_name', 'job_profile_target_name'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'requester_name') {
            $query->join('users as requester', 'procurements.id_requester', '=', 'requester.id_user')
                  ->orderBy('requester.name', $sortDir);
        } elseif ($sortBy === 'approver_name') {
            $query->join('users as approver', 'procurements.id_approver', '=', 'approver.id_user')
                  ->orderBy('approver.name', $sortDir);
        } elseif ($sortBy === 'job_profile_target_name') {
            $query->join('job_profiles', 'procurements.id_job_profile_target', '=', 'job_profiles.id_job_profile')
                  ->orderBy('job_profiles.profile_name', $sortDir);
        } else {
            $query->orderBy('procurements.' . $sortBy, $sortDir);
        }
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Procurement
    {
        $authUser = Auth::user();
        $query = Procurement::with([
            'requester',
            'requester.department',
            'requester.jobProfile',
            'approver',
            'jobProfileTarget'
        ])->where('id_procurement', $id);
        if (!in_array($authUser->role, ['admin', 'moderator']) && $query->first()->id_requester !== $authUser->id_user) {
            throw new AuthorizationException();
        }
        return $query->first();
    }

    public function create(array $data): Procurement
    {
        $procurement = DB::transaction(function () use ($data) {
            $procurementId = IdGenerator::generateUniqueProcurementId();
            $procurementData = array_merge($data, [
                'id_procurement'     => $procurementId,
                'procurement_status' => 'Diajukan',
            ]);
            $procurement = Procurement::create($procurementData);
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new NewRequestNotification($procurement, 'procurement'));
            return $procurement->load(['requester', 'jobProfileTarget']);
        });
        DataChanged::dispatch('procurements', 'created', 'admin', (string) $procurement->id_requester);
        return $procurement;
    }

    public function update(Procurement $procurement, array $data): Procurement
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            $updateProcurement = DB::transaction(function () use ($procurement, $data) {
                $procurement->update($data);
                return $procurement->fresh(['requester', 'jobProfileTarget']);
            });
        } else if ($user->role === 'employee' && $user->id_user === $procurement->id_requester) {
            if ($procurement->procurement_status !== 'Diajukan') {
                throw new Exception('Tidak dapat memperbarui pengajuan yang telah disetujui atau selesai.');
            }
            $allowedFields = [
                'id_job_profile_target',
                'item_name',
                'desired_specifications',
                'quantity',
                'reason',
            ];
            $filteredData = collect($data)
                ->only($allowedFields)
                ->toArray();
            $updateProcurement = DB::transaction(function () use ($procurement, $filteredData) {
                $procurement->update($filteredData);
                return $procurement->fresh(['requester', 'jobProfileTarget']);
            });
        } else {
            throw new Exception('Akses tidak diizinkan.', 403);
        }
        DataChanged::dispatch('procurements', 'updated', 'admin', (string) $procurement->id_requester);
        return $updateProcurement;
    }

    public function approve(Procurement $procurement, string $approverId, ?string $notes): Procurement
    {
        if ($procurement->procurement_status !== 'Diajukan') {
            throw new Exception('Pengajuan pengadaan telah diproses sebelumnya.');
        }
        $approve = DB::transaction(function () use ($procurement, $approverId, $notes) {
            $procurement->update([
                'procurement_status' => 'Disetujui',
                'id_approver'        => $approverId,
                'action_date'        => now(),
                'approver_notes'     => $notes,
            ]);
            $procurement->requester->notify(new ProcurementStatusUpdated($procurement, Auth::user()));
            return $procurement->fresh(['requester', 'approver', 'jobProfileTarget']);
        });
        DataChanged::dispatch('procurements', 'updated', 'admin', (string) $procurement->id_requester);
        return $approve;
    }

    public function reject(Procurement $procurement, string $approverId, string $notes): Procurement
    {
        if ($procurement->procurement_status !== 'Diajukan') {
            throw new Exception('Pengajuan pengadaan telah diproses sebelumnya.');
        }
        $reject = DB::transaction(function () use ($procurement, $approverId, $notes) {
            $procurement->update([
                'procurement_status' => 'Ditolak',
                'id_approver'        => $approverId,
                'action_date'        => now(),
                'approver_notes'     => $notes,
            ]);
            $procurement->requester->notify(new ProcurementStatusUpdated($procurement, Auth::user()));
            return $procurement->fresh(['requester', 'approver', 'jobProfileTarget']);
        });
        DataChanged::dispatch('procurements', 'updated', 'admin', (string) $procurement->id_requester);
        return $reject;
    }

    public function complete(Procurement $procurement): Procurement
    {
        if ($procurement->procurement_status !== 'Disetujui') {
            throw new Exception('Hanya pengajuan yang telah disetujui yang dapat diselesaikan.');
        }
        $procurement->update([
            'procurement_status' => 'Selesai',
        ]);
        DataChanged::dispatch('procurements', 'updated', 'admin', (string) $procurement->id_requester);
        return $procurement->fresh();
    }

    public function cancel(Procurement $procurement): void
    {
        $user = Auth::user();
        if ($user->id_user !== $procurement->id_requester) {
            throw new AuthorizationException();
        }
        if (!in_array($procurement->procurement_status, ['Diajukan'])) {
            throw new Exception('Tidak dapat membatalkan pengajuan yang sedang diproses atau selesai.');
        }
        $procurement->delete();
        DataChanged::dispatch('procurements', 'deleted', 'admin', (string) $user->id_user);
    }

    public function delete(Procurement $procurement): void
    {
        if (!in_array($procurement->procurement_status, ['Diajukan', 'Ditolak'])) {
            throw new Exception('Tidak dapat menghapus pengajuan yang sedang diproses atau selesai.');
        }
        $procurement->delete();
        DataChanged::dispatch('procurements', 'deleted', 'admin', (string) $procurement->id_requester);
    }

    public function getStatistics(): array
    {
        $stats = Procurement::toBase()
            ->selectRaw("
                COUNT(*) as total_requests,
                SUM(CASE WHEN procurement_status = 'Diajukan' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN procurement_status = 'Disetujui' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN procurement_status = 'Ditolak' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN procurement_status = 'Selesai' THEN 1 ELSE 0 END) as completed
            ")
            ->first();
        $today = Procurement::whereDate('created_at', today())->count();
        $thisWeek = Procurement::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = Procurement::whereMonth('request_date', now()->month)
            ->whereYear('request_date', now()->year)
            ->count();
        $byJobProfile = Procurement::selectRaw('id_job_profile_target, COUNT(*) as count')
            ->with('jobProfileTarget:id_job_profile,profile_name')
            ->groupBy('id_job_profile_target')
            ->get();
        $byRequester = Procurement::selectRaw('id_requester, COUNT(*) as count')
            ->with('requester:id_user,name')
            ->groupBy('id_requester')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        return [
            'total_requests' => (int) $stats->total_requests,
            'pending'        => (int) $stats->pending,
            'approved'       => (int) $stats->approved,
            'rejected'       => (int) $stats->rejected,
            'completed'      => (int) $stats->completed,
            'today'          => $today,
            'this_week'      => $thisWeek,
            'this_month'     => $thisMonth,
            'by_job_profile' => $byJobProfile,
            'by_requester'   => $byRequester,
        ];
    }

    public function getUserHistory(string $userId): Collection
    {
        return Procurement::with(['approver', 'jobProfileTarget'])
            ->where('id_requester', $userId)
            ->latest()
            ->get();
    }
}
