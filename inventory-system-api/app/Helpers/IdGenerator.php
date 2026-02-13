<?php

namespace App\Helpers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Str;

class IdGenerator
{
    /**
     * Generate Category ID from name
     * Example: "Komputer" -> "Kom", "Kabel HDMI" -> "KHDMI", "Papan Tulis" -> "PT"
     * If exists, add number: "PT2", "PT3", etc.
     */
    public static function generateCategoryId(string $categoryName): string
    {
        $cleaned = preg_replace('/[^a-zA-Z\s]/', '', $categoryName);
        $cleaned = trim($cleaned);
        $words = explode(' ', $cleaned);
        $code = '';
        if (count($words) === 1) {
            $code = Str::upper(substr($words[0], 0, 3));
        } else {
            foreach ($words as $word) {
                if (!empty($word)) {
                    $code .= Str::upper(substr($word, 0, 1));
                }
            }
        }
        $baseCode = $code;
        $counter = 1;
        while (Category::where('id_category', $code)->exists()) {
            $counter++;
            $code = $baseCode . $counter;
        }
        return $code;
    }

    /**
     * Generate Location ID from name
     * Example: "Gedung 1" -> "G1-001", "Gedung Anggrek" -> "GA-001"
     * If exists: "GA2-001", "GA3-001", etc.
     */
    public static function generateLocationId(string $building): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $building);
        $words = array_filter(explode(' ', trim($cleaned)));
        $basePrefix = '';
        foreach ($words as $word) {
            $basePrefix .= is_numeric($word)
                ? $word
                : strtoupper(substr($word, 0, 1));
        }
        $locations = Location::where('id_location', 'like', $basePrefix . '%')
            ->get(['id_location', 'building']);
        $groups = [];
        foreach ($locations as $loc) {
            if (preg_match('/^(' . preg_quote($basePrefix) . '\d*)-(\d{3})$/', $loc->id_location, $m)) {
                $prefix = $m[1];
                $groups[$prefix][] = $loc;
            }
        }
        $finalPrefix = null;
        foreach ($groups as $prefix => $items) {
            if ($items[0]->building === $building) {
                $finalPrefix = $prefix;
                break;
            }
        }
        if (!$finalPrefix) {
            $i = 1;
            do {
                $candidate = $i === 1 ? $basePrefix : $basePrefix . $i;
                $exists = array_key_exists($candidate, $groups);
                $i++;
            } while ($exists);

            $finalPrefix = $candidate;
        }
        $usedNumbers = [];

        foreach ($groups[$finalPrefix] ?? [] as $loc) {
            if (preg_match('/-(\d{3})$/', $loc->id_location, $m)) {
                $usedNumbers[] = (int) $m[1];
            }
        }
        $nextNumber = empty($usedNumbers)
            ? 1
            : max($usedNumbers) + 1;

        return $finalPrefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate User ID from department
     * Example: Department "3" -> "3-0001", "3-0002", etc.
     */
    public static function generateUserId(int $departmentId): string
    {
        $lastUser = User::where('id_user', 'like', $departmentId . '-%')
            ->orderByRaw("CAST(SUBSTR(id_user, LENGTH(?) + 2) AS INTEGER) DESC", [(string)$departmentId])
            ->first();
        $number = 1;
        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->id_user, strlen((string)$departmentId) + 1);
            $number = $lastNumber + 1;
        }
        return $departmentId . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Asset ID from category
     * Example: Category "Kom" -> "Kom-0001", "Kom-0002", etc.
     */
    public static function generateAssetId(string $categoryId): string
    {
        $prefixLength = strlen($categoryId);
        $lastAsset = Asset::where('id_asset', 'like', $categoryId . '-%')
            ->orderByRaw("CAST(SUBSTR(id_asset, ?) AS INTEGER) DESC", [$prefixLength + 2])
            ->first();
        $number = 1;
        if ($lastAsset) {
            $lastNumber = (int) substr($lastAsset->id_asset, $prefixLength + 1);
            $number = $lastNumber + 1;
        }
        return $categoryId . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate and suggest Category ID
     */
    public static function suggestCategoryId(string $categoryName): array
    {
        $suggestedId = self::generateCategoryId($categoryName);
        return [
            'suggested_id' => $suggestedId,
            'is_available' => !Category::where('id_category', $suggestedId)->exists()
        ];
    }

    /**
     * Validate and suggest Location ID
     */
    public static function suggestLocationId(string $building): array
    {
        $suggestedId = self::generateLocationId($building);
        return [
            'suggested_id' => $suggestedId,
            'is_available' => !Location::where('id_location', $suggestedId)->exists()
        ];
    }

    /**
     * Validate and suggest User ID
     */
    public static function suggestUserId(int $departmentId): array
    {
        $suggestedId = self::generateUserId($departmentId);
        return [
            'suggested_id' => $suggestedId,
            'is_available' => !User::where('id_user', $suggestedId)->exists()
        ];
    }

    /**
     * Validate and suggest Asset ID
     */
    public static function suggestAssetId(string $categoryId): array
    {
        $suggestedId = self::generateAssetId($categoryId);
        return [
            'suggested_id' => $suggestedId,
            'is_available' => !Asset::where('id_asset', $suggestedId)->exists()
        ];
    }

    /**
     * Generate image file name
     * Example: Asset "Kom-0001" -> "Kom-0001-001.jpg", etc.
     */
    public static function generateImageFileName($asset, $extension)
    {
        $count = $asset->images()->count() + 1;
        $number = str_pad($count, 3, '0', STR_PAD_LEFT);
        return $asset->id_asset . '-' . $number . '.' . $extension;
    }

    /**
     * Generate unique ID with retry mechanism
     * Adds microseconds if collision detected
     */
    private static function generateUniqueId(string $prefix, $model): string
    {
        $maxRetries = 5;
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $date = now()->format('Ymd');
            $time = now()->format('His');
            $microtime = substr(microtime(), 2, 6);
            $id = "{$prefix}-{$date}-{$time}-{$microtime}";
            $primaryKey = (new $model)->getKeyName();
            $exists = $model::where($primaryKey, $id)->exists();
            if (!$exists) {
                return $id;
            }
            $attempt++;
            usleep(100);
        }
        $random = Str::random(4);
        return "{$prefix}-{$date}-{$time}-{$random}";
    }

    /**
     * Generate unique Loan ID with collision handling
     */
    public static function generateUniqueLoanId(): string
    {
        return self::generateUniqueId('LOAN', \App\Models\Loan::class);
    }

    /**
     * Generate unique Maintenance ID with collision handling
     */
    public static function generateUniqueMaintenanceId(): string
    {
        return self::generateUniqueId('MTNC', \App\Models\Maintenance::class);
    }

    /**
     * Generate unique Procurement ID with collision handling
     */
    public static function generateUniqueProcurementId(): string
    {
        return self::generateUniqueId('CURE', \App\Models\Procurement::class);
    }
}
