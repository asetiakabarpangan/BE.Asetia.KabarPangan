<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\{Request, JsonResponse};

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'required|in:asset,loan,maintenance,procurement,user,category,department,job_profile',
            'range' => 'required|in:week,month,year,custom',
            'start_date' => 'nullable|required_if:range,custom|date',
            'end_date' => 'nullable|required_if:range,custom|date|after_or_equal:start_date',
        ]);
        $data = $this->dashboardService->getHistory(
            $request->module,
            $request->range,
            $request->start_date,
            $request->end_date
        );
        return $this->success($data);
    }
}
