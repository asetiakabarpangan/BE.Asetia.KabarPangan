<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\Hash;
use App\Events\DataChanged;
use App\Helpers\IdGenerator;
use App\Models\Asset;
use App\Models\User;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\JobProfile;
use App\Models\Loan;
use App\Models\Procurement;
use App\Models\Maintenance;

class UniversalImport implements ToModel, WithHeadingRow, WithEvents
{
    protected $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function model(array $row)
    {
        if (empty($row)) {
            return null;
        }
        switch ($this->table) {
            case 'assets':
                $idAsset = $this->resolveId(
                    Asset::class,
                    'id_asset',
                    $row['id_asset'] ?? null,
                    function() use ($row) {
                        return IdGenerator::generateAssetId($row['id_category']);
                    }
                );
                if (!$idAsset) return null;
                $specs = $row['specification'] ?? [];
                if (is_string($specs)) {
                    $decoded = json_decode($specs, true);
                    if (json_last_error() === JSON_ERROR_NONE) $specs = $decoded;
                }
                return new Asset([
                    'id_asset'            => $idAsset,
                    'asset_name'          => $row['asset_name'],
                    'brand'               => $row['brand'],
                    'id_category'         => $row['id_category'],
                    'id_location'         => $row['id_location'],
                    'condition'           => $row['condition'],
                    'acquisition_date'    => $this->transformDate($row['acquisition_date'] ?? null),
                    'availability_status' => $row['availability_status'] ?? 'Tersedia',
                    'information'         => $row['information'] ?? null,
                    'specification'       => $specs,
                ]);
            case 'users':
                $idUser = $this->resolveId(
                    User::class,
                    'id_user',
                    $row['id_user'] ?? null,
                    function() use ($row) {
                        return IdGenerator::generateUserId($row['role'] ?? 'employee');
                    }
                );
                if (!$idUser) return null;
                $password = isset($row['password']) && $row['password']
                            ? Hash::make($row['password'])
                            : Hash::make('password123');
                return new User([
                    'id_user'        => $idUser,
                    'name'           => $row['name'],
                    'email'          => $row['email'],
                    'password'       => $password,
                    'role'           => $row['role'] ?? 'employee',
                    'position'       => $row['position'],
                    'id_department'  => $row['id_department'],
                    'id_job_profile' => $row['id_job_profile'] ?? null,
                ]);
            case 'categories':
                $idCategory = $this->resolveId(
                    Category::class,
                    'id_category',
                    $row['id_category'] ?? null,
                    function() use ($row) {
                        return IdGenerator::generateCategoryId($row['category_name']);
                    }
                );
                if (!$idCategory) return null;
                return new Category([
                    'id_category'   => $idCategory,
                    'category_name' => $row['category_name'],
                ]);
            case 'locations':
                $idLocation = $this->resolveId(
                    Location::class,
                    'id_location',
                    $row['id_location'] ?? null,
                    function() use ($row) {
                        return IdGenerator::generateLocationId($row['building']);
                    }
                );
                if (!$idLocation) return null;
                return new Location([
                    'id_location'         => $idLocation,
                    'location_name'       => $row['location_name'],
                    'building'            => $row['building'],
                    'id_person_in_charge' => $row['id_person_in_charge'] ?? null,
                ]);
            case 'departments':
                $data = ['department_name' => $row['department_name']];
                if (!empty($row['id_department'])) {
                     if (Department::where('id_department', $row['id_department'])->exists()) return null;
                     $data['id_department'] = $row['id_department'];
                }
                return new Department($data);
            case 'job_profiles':
                $data = [
                    'profile_name' => $row['profile_name'],
                    'description'  => $row['description'] ?? null
                ];
                if (!empty($row['id_job_profile'])) {
                     if (JobProfile::where('id_job_profile', $row['id_job_profile'])->exists()) return null;
                     $data['id_job_profile'] = $row['id_job_profile'];
                }
                return new JobProfile($data);
            case 'loans':
                $idLoan = $this->resolveId(
                    Loan::class,
                    'id_loan',
                    $row['id_loan'] ?? null,
                    function() { return IdGenerator::generateUniqueLoanId(); }
                );
                if (!$idLoan) return null;
                return new Loan([
                    'id_loan'     => $idLoan,
                    'id_asset'    => $row['id_asset'],
                    'id_user'     => $row['id_user'],
                    'borrow_date' => $this->transformDate($row['borrow_date'] ?? null),
                    'due_date'    => $this->transformDate($row['due_date'] ?? null),
                    'return_date' => $this->transformDate($row['return_date'] ?? null),
                    'loan_status' => $row['loan_status'] ?? 'Menunggu Konfirmasi Peminjaman',
                ]);
            case 'procurements':
                $idProc = $this->resolveId(
                    Procurement::class,
                    'id_procurement',
                    $row['id_procurement'] ?? null,
                    function() { return IdGenerator::generateUniqueProcurementId(); }
                );
                if (!$idProc) return null;
                return new Procurement([
                    'id_procurement'         => $idProc,
                    'id_requester'           => $row['id_requester'],
                    'id_job_profile_target'  => $row['id_job_profile_target'],
                    'item_name'              => $row['item_name'],
                    'desired_specifications' => $row['desired_specifications'],
                    'quantity'               => $row['quantity'],
                    'reason'                 => $row['reason'],
                    'request_date'           => $this->transformDate($row['request_date'] ?? now()),
                    'procurement_status'     => $row['procurement_status'] ?? 'Diajukan',
                    'id_approver'            => $row['id_approver'] ?? null,
                    'action_date'            => $this->transformDate($row['action_date'] ?? null),
                    'approver_notes'         => $row['approver_notes'] ?? null,
                ]);
            case 'maintenances':
                $idMaint = $this->resolveId(
                    Maintenance::class,
                    'id_maintenance',
                    $row['id_maintenance'] ?? null,
                    function() { return IdGenerator::generateUniqueMaintenanceId(); }
                );
                if (!$idMaint) return null;
                return new Maintenance([
                    'id_maintenance'         => $idMaint,
                    'id_asset'               => $row['id_asset'],
                    'id_maintenance_officer' => $row['id_maintenance_officer'],
                    'maintenance_status'     => $row['maintenance_status'] ?? 'Dalam Perbaikan',
                    'maintenance_date'       => $this->transformDate($row['maintenance_date']),
                    'finish_date'            => $this->transformDate($row['finish_date'] ?? null),
                    'maintenance_detail'     => $row['maintenance_detail'],
                    'maintenance_cost'       => $row['maintenance_cost'] ?? 0,
                ]);
            default:
                return null;
        }
    }

    /**
     * Helper Sakti: Menangani logika "Gunakan Input ATAU Generate Baru"
     * serta mengecek duplikasi di DB.
     * * @param string $modelClass Nama Class Model (misal: Asset::class)
     * @param string $primaryKey Nama kolom PK (misal: 'id_asset')
     * @param mixed $inputId ID dari Excel (bisa null/empty)
     * @param callable $generatorCallback Fungsi untuk generate ID jika input kosong
     * @return string|null ID yang valid, atau NULL jika ID input sudah terpakai
     */
    private function resolveId($modelClass, $primaryKey, $inputId, $generatorCallback)
    {
        if (!empty($inputId)) {
            $exists = $modelClass::where($primaryKey, $inputId)->exists();
            if ($exists) {
                return null;
            }
            return $inputId;
        }
        return $generatorCallback();
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                DataChanged::dispatch($this->table, 'created', 'all');
            },
        ];
    }

    private function transformDate($value)
    {
        if (!$value) return null;
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }
        return $value;
    }
}
