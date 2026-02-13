<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyStatistic;
use App\Services\DepartmentService;
use App\Services\JobProfileService;
use App\Services\AssetService;
use App\Services\CategoryService;
use App\Services\LoanService;
use App\Services\MaintenanceService;
use App\Services\ProcurementService;
use App\Services\UserService;

class SnapshotDailyStats extends Command
{
    protected $signature = 'stats:snapshot';
    protected $description = 'Simpan snapshot statistik harian ke database';

    public function __construct(
        private CategoryService $categoryService,
        private DepartmentService $departmentService,
        private JobProfileService $jobProfileService,
        private UserService $userService,
        private AssetService $assetService,
        private LoanService $loanService,
        private MaintenanceService $maintenanceService,
        private ProcurementService $procurementService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $date = now()->toDateString();
        $this->info("Memulai snapshot statistik untuk tanggal: $date");
        $this->saveSnapshot($date, 'category', $this->categoryService->getStatistics());
        $this->saveSnapshot($date, 'department', $this->departmentService->getStatistics());
        $this->saveSnapshot($date, 'job_profile', $this->jobProfileService->getStatistics());
        $this->saveSnapshot($date, 'user', $this->userService->getStatistics());
        $this->saveSnapshot($date, 'asset', $this->assetService->getStatistics());
        $this->saveSnapshot($date, 'loan', $this->loanService->getStatistics());
        $this->saveSnapshot($date, 'maintenance', $this->maintenanceService->getStatistics());
        $this->saveSnapshot($date, 'procurement', $this->procurementService->getStatistics());
        $this->info('Snapshot selesai.');
    }

    private function saveSnapshot($date, $module, $data)
    {
        DailyStatistic::updateOrCreate(
            ['date' => $date, 'module' => $module],
            ['data' => $data]
        );
        $this->info("Saved $module stats.");
    }
}
