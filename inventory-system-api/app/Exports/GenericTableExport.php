<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Loan;
use App\Models\Procurement;
use App\Models\Maintenance;
use App\Models\Location;

class GenericTableExport implements FromCollection, WithHeadings, WithTitle, WithMapping, ShouldAutoSize
{
    protected $tableName;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    public function collection()
    {
        switch ($this->tableName) {
            case 'users':
                return User::with(['department', 'jobProfile'])->get();
            case 'loans':
                return Loan::with(['user', 'asset'])->get();
            case 'procurements':
                return Procurement::with(['requester', 'jobProfileTarget', 'approver'])->get();
            case 'maintenances':
                return Maintenance::with(['asset', 'maintenanceOfficer'])->get();
            case 'locations':
                return Location::with(['personInCharge'])->get();
            default:
                return DB::table($this->tableName)->get();
        }
    }

    public function map($row): array
    {
        if ($this->tableName === 'users') {
            return [
                $row->id_user,
                $row->name,
                $row->email,
                $row->role,
                $row->department ? "{$row->id_department} - {$row->department->department_name}" : '-',
                $row->jobProfile ? "{$row->id_job_profile} - {$row->jobProfile->profile_name}" : '-',
            ];
        }
        if ($this->tableName === 'loans') {
            return [
                $row->id_loan,
                $row->asset ? "{$row->id_asset} - {$row->asset->asset_name}" : $row->id_asset,
                $row->user ? "{$row->id_user} - {$row->user->name}" : $row->id_user,
                $row->borrow_date ? $row->borrow_date->format('Y-m-d H:i') : '-',
                $row->due_date ? $row->due_date->format('Y-m-d H:i') : '-',
                $row->return_date ? $row->return_date->format('Y-m-d H:i') : '-',
                $row->loan_status,
            ];
        }
        if ($this->tableName === 'procurements') {
            return [
                $row->id_procurement,
                $row->requester ? "{$row->id_requester} - {$row->requester->name}" : $row->id_requester,
                $row->jobProfileTarget ? "{$row->id_job_profile_target} - {$row->jobProfileTarget->profile_name}" : $row->id_job_profile_target,
                $row->item_name,
                $row->quantity,
                $row->request_date ? $row->request_date->format('Y-m-d') : '-',
                $row->procurement_status,
                $row->approver ? "{$row->id_approver} - {$row->approver->name}" : '-',
                $row->action_date ? $row->action_date->format('Y-m-d') : '-',
                $row->approver_notes
            ];
        }
        if ($this->tableName === 'maintenances') {
            return [
                $row->id_maintenance,
                $row->asset ? "{$row->id_asset} - {$row->asset->asset_name}" : $row->id_asset,
                $row->maintenanceOfficer ? "{$row->id_maintenance_officer} - {$row->maintenanceOfficer->name}" : $row->id_maintenance_officer,
                $row->maintenance_status,
                $row->maintenance_date ? $row->maintenance_date->format('Y-m-d') : '-',
                $row->finish_date ? $row->finish_date->format('Y-m-d') : '-',
                $row->maintenance_detail,
                $row->maintenance_cost,
            ];
        }
        if ($this->tableName === 'locations') {
            return [
                $row->id_location,
                $row->location_name,
                $row->building,
                $row->personInCharge ? "{$row->id_person_in_charge} - {$row->personInCharge->name}" : '-', //
            ];
        }
        $data = (array) $row;
        return array_map(function ($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_PRETTY_PRINT);
            }
            return $value;
        }, array_values($data));
    }

    public function headings(): array
    {
        switch ($this->tableName) {
            case 'users':
                return ['ID User', 'Name', 'Email', 'Role', 'Department', 'Job Profile'];
            case 'loans':
                return ['ID Loan', 'Asset', 'Borrower', 'Borrow Date', 'Due Date', 'Return Date', 'Status'];
            case 'procurements':
                return ['ID Proc', 'Requester', 'Target Job', 'Item Name', 'Qty', 'Req Date', 'Status', 'Approver', 'Action Date', 'Notes'];
            case 'maintenances':
                return ['ID Maint', 'Asset', 'Officer', 'Status', 'Start Date', 'Finish Date', 'Detail', 'Cost'];
            case 'locations':
                return ['ID Location', 'Location Name', 'Building', 'Person In Charge'];
            default:
                return Schema::getColumnListing($this->tableName);
        }
    }

    public function title(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tableName));
    }
}
