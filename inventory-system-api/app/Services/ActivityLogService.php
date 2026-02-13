<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = ActivityLog::select('activity_logs.*')->with(['actor']);
        $search = $filters['search'] ?? null;
        $action = $filters['action'] ?? null;
        $modelName = $filters['model'] ?? null;
        $actorId = $filters['actor'] ?? null;
        $theDate = $filters['the_date'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'ilike', "%{$search}%")
                  ->orWhere('data_name', 'ilike', "%{$search}%")
                  ->orWhereHas('actor', function ($subQ) use ($search) {
                      $subQ->where('name', 'ilike', "%{$search}%");
                  });
            });
        }
        if ($action) {
            $query->where('action', $action);
        }
        if ($modelName) {
            $query->where('model_name', $modelName);
        }
        if ($actorId) {
            $query->where('actor_id', $actorId);
        }
        if ($startDate || $endDate) {
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        } else if ($theDate) {
            $query->whereDate('created_at', $theDate);
        }
        $allowSortBy = ['created_at', 'action', 'model_name', 'data_name', 'actor_name'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'actor_name') {
            $query->join('users', 'activity_logs.actor_id', '=', 'users.id_user')
                  ->orderBy('users.name', $sortDir);
        } else {
            $query->orderBy('activity_logs.' . $sortBy, $sortDir);
        }
        return $query->paginate($perPage);
    }
}
