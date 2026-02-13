<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainDataExport;
use App\Exports\GroupedExport;
use App\Imports\UniversalImport;
use Illuminate\Http\UploadedFile;

class DataPortService
{
    public function exportData(array $tables, string $format = 'xlsx', string $mode = 'standard')
    {
        $writerType = $format === 'pdf'
            ? \Maatwebsite\Excel\Excel::DOMPDF
            : \Maatwebsite\Excel\Excel::XLSX;
        $fileName = 'export_' . $mode . '_' . date('Ymd_His') . '.' . $format;
        if ($mode === 'grouped') {
            $targetTable = $tables[0];
            return Excel::download(new GroupedExport($targetTable), $fileName, $writerType);
        }
        return Excel::download(new MainDataExport($tables), $fileName, $writerType);
    }

    public function importData(UploadedFile $file, string $targetTable): void
    {
        Excel::import(new UniversalImport($targetTable), $file);
    }
}
