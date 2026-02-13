<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search' => $request->get('search'),
            'action' => $request->get('action'),
            'model' => $request->get('model'),
            'actor' => $request->get('actor_id'),
            'the_date' => $request->get('the_date'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'sortBy' => $request->get('sort_by', 'created_at'),
            'sortDir' => $request->get('sort_dir', 'desc'),
        ];
        $logs = $this->activityLogService->getPaginated($filters, $perPage);
        return $this->success($logs);
    }
}
