<?php

namespace App\Services;

use App\Models\DailyStatistic;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardService
{
    public function getHistory(string $module, string $range, ?string $customStart = null, ?string $customEnd = null): array
    {
        if ($range === 'custom' && $customStart && $customEnd) {
            $startDate = Carbon::parse($customStart);
            $endDate = Carbon::parse($customEnd);
        } else {
            $endDate = now();
            $startDate = match ($range) {
                'week' => now()->subDays(6),
                'month' => now()->subDays(29),
                'year' => now()->subDays(364),
                default => now()->subDays(29),
            };
        }
        $durationInDays = $startDate->diffInDays($endDate) + 1;
        $previousEndDate = $startDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($durationInDays - 1);
        $currentStats = DailyStatistic::where('module', $module)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();
        $previousStats = DailyStatistic::where('module', $module)
            ->whereBetween('date', [$previousStartDate->toDateString(), $previousEndDate->toDateString()])
            ->get();
        $chartData = $this->formatForChart($currentStats, $module);
        $summary = $this->calculateGrowth($currentStats, $previousStats, $module, $durationInDays);
        return array_merge($chartData, ['summary' => $summary]);
    }

    private function calculateGrowth(Collection $current, Collection $previous, string $module, int $daysCount): array
    {
        $keyMap = [
            'asset' => 'total_assets',
            'loan' => 'active_loans',
            'maintenance' => 'total_cost',
            'procurement' => 'total_requests',
            'user' => 'total_users',
            'category' => 'total_categories',
            'department' => 'total_departments',
            'job_profile' => 'total_job_profiles',
        ];
        $key = $keyMap[$module] ?? 'count';
        $currentAvg = $current->isEmpty() ? 0 : $current->avg(fn($s) => $s->data[$key] ?? 0);
        $previousAvg = $previous->isEmpty() ? 0 : $previous->avg(fn($s) => $s->data[$key] ?? 0);
        $currentAvg = round($currentAvg, 2);
        $previousAvg = round($previousAvg, 2);
        $diff = $currentAvg - $previousAvg;
        if ($previousAvg > 0) {
            $percentage = ($diff / $previousAvg) * 100;
        } else {
            $percentage = $currentAvg > 0 ? 100 : 0;
        }
        $trend = $diff >= 0 ? 'increase' : 'decrease';
        return [
            'current_value' => $currentAvg,
            'previous_value' => $previousAvg,
            'difference' => abs($diff),
            'percentage' => round(abs($percentage), 1),
            'trend' => $trend,
            'metric_label' => $this->getMetricLabel($module),
            'duration_days' => $daysCount
        ];
    }

    private function getMetricLabel(string $module): string {
        return match($module) {
            'asset' => 'Unit Aset',
            'loan' => 'Peminjaman',
            'maintenance' => 'Biaya (Rp)',
            'procurement' => 'Pengajuan',
            'user' => 'User',
            default => 'Data',
        };
    }

    private function formatForChart(Collection $stats, string $module): array
    {
        $labels = $stats->pluck('date')->map(fn($d) => $d->format('d M'))->toArray();
        $dataPoints = [];
        switch ($module) {
            case 'asset':
                $dataPoints = [
                    [
                        'name' => 'Total Aset',
                        'data' => $stats->map(fn($s) => $s->data['total_assets'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Tersedia',
                        'data' => $stats->map(fn($s) => $s->data['available'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Rusak',
                        'data' => $stats->map(fn($s) => $s->data['damaged'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Hilang',
                        'data' => $stats->map(fn($s) => $s->data['lost'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'loan':
                $dataPoints = [
                    [
                        'name' => 'Total Transaksi',
                        'data' => $stats->map(fn($s) => $s->data['total_loans'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Sedang Dipinjam',
                        'data' => $stats->map(fn($s) => $s->data['active_loans'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Menunggu Approval',
                        'data' => $stats->map(fn($s) => $s->data['pending_approval'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Terlambat',
                        'data' => $stats->map(fn($s) => $s->data['overdue'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'maintenance':
                $dataPoints = [
                    [
                        'name' => 'Total Maintenance',
                        'data' => $stats->map(fn($s) => $s->data['total_maintenances'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Biaya Total (Rp)',
                        'data' => $stats->map(fn($s) => $s->data['total_cost'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Rata-rata Biaya (Rp)',
                        'data' => $stats->map(fn($s) => $s->data['average_cost'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'procurement':
                $dataPoints = [
                    [
                        'name' => 'Total Pengajuan',
                        'data' => $stats->map(fn($s) => $s->data['total_requests'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Menunggu',
                        'data' => $stats->map(fn($s) => $s->data['pending'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Disetujui',
                        'data' => $stats->map(fn($s) => $s->data['approved'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Selesai',
                        'data' => $stats->map(fn($s) => $s->data['completed'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'user':
                $dataPoints = [
                    [
                        'name' => 'Total Pengguna',
                        'data' => $stats->map(fn($s) => $s->data['total_users'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Pengguna Baru',
                        'data' => $stats->map(fn($s) => $s->data['new_today'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'category':
                $dataPoints = [
                    [
                        'name' => 'Total Kategori',
                        'data' => $stats->map(fn($s) => $s->data['total_categories'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Kategori dgn Aset',
                        'data' => $stats->map(fn($s) => $s->data['with_assets'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'department':
                $dataPoints = [
                    [
                        'name' => 'Total Departemen',
                        'data' => $stats->map(fn($s) => $s->data['total_departments'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Departemen dgn User',
                        'data' => $stats->map(fn($s) => $s->data['with_users'] ?? 0)->toArray()
                    ]
                ];
                break;
            case 'job_profile':
                $dataPoints = [
                    [
                        'name' => 'Total Job Profile',
                        'data' => $stats->map(fn($s) => $s->data['total_job_profiles'] ?? 0)->toArray()
                    ],
                    [
                        'name' => 'Ada Rekomendasi',
                        'data' => $stats->map(fn($s) => $s->data['with_recommendations'] ?? 0)->toArray()
                    ]
                ];
                break;
        }
        return [
            'labels' => $labels,
            'series' => $dataPoints
        ];
    }
}
