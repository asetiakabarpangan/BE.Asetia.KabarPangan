<?php

namespace App\Exports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GroupedExport implements WithMultipleSheets
{
    use Exportable;

    protected $targetTable;

    public function __construct($targetTable)
    {
        $this->targetTable = $targetTable;
    }

    public function sheets(): array
    {
        $sheets = [];
        $locations = Location::all();
        foreach ($locations as $location) {
            $sheets[] = new LocationSheetExport($location, $this->targetTable);
        }
        return $sheets;
    }
}
