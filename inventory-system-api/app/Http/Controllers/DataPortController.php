<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataPort\{ExportDataRequest, ImportDataRequest};
use App\Services\DataPortService;
use Illuminate\Http\JsonResponse;

class DataPortController extends Controller
{
    protected $dataPortService;

    public function __construct(DataPortService $dataPortService)
    {
        $this->dataPortService = $dataPortService;
    }

    public function export(ExportDataRequest $request)
    {
        $validated = $request->validated();
        $tables = $validated['tables'];
        $format = $validated['format'] ?? 'xlsx';
        $mode   = $validated['mode'] ?? 'standard';
        return $this->dataPortService->exportData($tables, $format, $mode);
    }

    public function import(ImportDataRequest $request): JsonResponse
    {
        try {
            $this->dataPortService->importData(
                $request->file('file'),
                $request->input('table')
            );
            return $this->success(null, 'Data berhasil diimport.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return $this->error('Gagal Import Data', 422, $e->failures());
        } catch (\Exception $e) {
            return $this->error('Terjadi kesalahan server: ' . $e->getMessage(), 500, $e);
        }
    }
}
