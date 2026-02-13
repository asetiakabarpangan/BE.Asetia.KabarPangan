<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetCompleteExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return Asset::query()->with([
            'category',
            'location',
            'images',
            'recommendations.jobProfile'
        ]);
    }

    public function map($asset): array
    {
        $categoryLabel = $asset->category
            ? "{$asset->id_category} - {$asset->category->category_name}"
            : $asset->id_category;
        $locationLabel = $asset->location
            ? "{$asset->id_location} - {$asset->location->location_name}"
            : $asset->id_location;
        $specsList = '-';
        if (!empty($asset->specification) && is_array($asset->specification)) {
            $specsList = collect($asset->specification)
                ->map(function ($value, $key) {
                    $formattedKey = ucwords(str_replace('_', ' ', $key));
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    return "{$formattedKey}: {$value}";
                })
                ->implode("\n");
        }
        $imagesList = $asset->images->map(function ($img) {
            return $img->image_url . ($img->description ? " ({$img->description})" : "");
        })->implode("\n");
        $recsList = $asset->recommendations->map(function ($rec) {
            return $rec->jobProfile
                ? "{$rec->id_job_profile} - {$rec->jobProfile->profile_name}"
                : $rec->id_job_profile;
        })->implode("\n");
        return [
            $asset->id_asset,
            $asset->asset_name,
            $asset->brand,
            $categoryLabel,
            $specsList,
            $locationLabel,
            $asset->condition,
            $asset->availability_status,
            $imagesList,
            $recsList,
            $asset->acquisition_date ? $asset->acquisition_date->format('Y-m-d') : '-',
            $asset->information,
        ];
    }

    public function headings(): array
    {
        return [
            'ID Asset',
            'Asset Name',
            'Brand',
            'Category',
            'Specifications',
            'Location',
            'Condition',
            'Status',
            'Images',
            'Recommendations',
            'Acquisition Date',
            'Information'
        ];
    }

    public function title(): string
    {
        return 'Assets Complete';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'E' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'I' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'J' => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'A:L' => ['alignment' => ['vertical' => 'top']],
        ];
    }
}
