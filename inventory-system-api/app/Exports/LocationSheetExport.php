<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocationSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $location;
    protected $type;

    public function __construct($location, $type)
    {
        $this->location = $location;
        $this->type = $type;
    }

    public function query()
    {
        $locId = $this->location->id_location;
        if ($this->type === 'assets') {
            return Asset::query()
                ->with(['category'])
                ->where('id_location', $locId)
                ->join('categories', 'assets.id_category', '=', 'categories.id_category')
                ->orderBy('categories.category_name')
                ->select('assets.*');
        }
        if ($this->type === 'loans') {
            return Loan::query()
                ->with(['asset.category', 'user'])
                ->whereHas('asset', function (Builder $query) use ($locId) {
                    $query->where('id_location', $locId);
                })
                ->join('assets', 'loans.id_asset', '=', 'assets.id_asset')
                ->join('categories', 'assets.id_category', '=', 'categories.id_category')
                ->orderBy('categories.category_name')
                ->select('loans.*');
        }
        if ($this->type === 'maintenances') {
            return Maintenance::query()
                ->with(['asset.category', 'maintenanceOfficer'])
                ->whereHas('asset', function (Builder $query) use ($locId) {
                    $query->where('id_location', $locId);
                })
                ->join('assets', 'maintenances.id_asset', '=', 'assets.id_asset')
                ->join('categories', 'assets.id_category', '=', 'categories.id_category')
                ->orderBy('categories.category_name')
                ->select('maintenances.*');
        }
    }

    public function map($row): array
    {
        $catLabel = function($asset) {
            return $asset->category
                ? "{$asset->category->category_name}"
                : '-';
        };
        if ($this->type === 'assets') {
            $specs = '-';
            if (!empty($row->specification) && is_array($row->specification)) {
                $specs = collect($row->specification)
                    ->map(function($v, $k) {
                        if (is_array($v)) {
                            $v = json_encode($v);
                        }
                        return ucwords(str_replace('_', ' ', $k)) . ": $v";
                    })
                    ->implode("\n");
            }
            return [
                $catLabel($row),
                $row->id_asset,
                $row->asset_name,
                $row->brand,
                $specs,
                $row->condition,
                $row->availability_status,
                $row->acquisition_date ? $row->acquisition_date->format('Y-m-d') : '-',
                $row->information
            ];
        }
        if ($this->type === 'loans') {
            return [
                $catLabel($row->asset),
                $row->id_loan,
                $row->asset ? "{$row->asset->asset_name} ({$row->id_asset})" : '-',
                $row->user ? "{$row->user->name} ({$row->id_user})" : '-',
                $row->loan_status,
                $row->borrow_date ? $row->borrow_date->format('Y-m-d H:i') : '-',
                $row->due_date ? $row->due_date->format('Y-m-d H:i') : '-',
                $row->return_date ? $row->return_date->format('Y-m-d H:i') : '-',
            ];
        }
        if ($this->type === 'maintenances') {
            return [
                $catLabel($row->asset),
                $row->id_maintenance,
                $row->asset ? "{$row->asset->asset_name} ({$row->id_asset})" : '-',
                $row->maintenanceOfficer ? $row->maintenanceOfficer->name : '-',
                $row->maintenance_status,
                $row->maintenance_date ? $row->maintenance_date->format('Y-m-d') : '-',
                $row->finish_date ? $row->finish_date->format('Y-m-d') : '-',
                $row->maintenance_cost ? "Rp " . number_format($row->maintenance_cost, 0, ',', '.') : '-',
                $row->maintenance_detail
            ];
        }
        return [];
    }

    public function headings(): array
    {
        $commonHead = ['CATEGORY GROUP'];
        if ($this->type === 'assets') {
            return array_merge($commonHead, [
                'ID Asset', 'Name', 'Brand', 'Specifications',
                'Condition', 'Status', 'Acq. Date', 'Information'
            ]);
        }
        if ($this->type === 'loans') {
            return array_merge($commonHead, [
                'ID Loan', 'Asset', 'Borrower', 'Status',
                'Borrow Date', 'Due Date', 'Return Date'
            ]);
        }
        if ($this->type === 'maintenances') {
            return array_merge($commonHead, [
                'ID Maint', 'Asset', 'Officer', 'Status',
                'Start Date', 'Finish Date', 'Cost', 'Detail'
            ]);
        }
        return [];
    }

    public function title(): string
    {
        return substr($this->location->location_name, 0, 30);
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            'A:Z' => ['alignment' => ['vertical' => 'top']],
        ];
        if ($this->type === 'assets') {
            $sheet->getStyle('E')->getAlignment()->setWrapText(true);
            $sheet->getStyle('I')->getAlignment()->setWrapText(true);
        }
        if ($this->type === 'maintenances') {
            $sheet->getStyle('I')->getAlignment()->setWrapText(true);
        }
        return $styles;
    }
}
