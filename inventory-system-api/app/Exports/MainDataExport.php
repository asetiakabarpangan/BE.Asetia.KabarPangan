<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MainDataExport implements WithMultipleSheets
{
    use Exportable;

    protected $selectedTables;

    public function __construct(array $selectedTables)
    {
        $this->selectedTables = $selectedTables;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->selectedTables as $table) {
            if ($table === 'assets') {
                $sheets[] = new AssetCompleteExport();
            } else {
                $sheets[] = new GenericTableExport($table);
            }
        }
        return $sheets;
    }
}
