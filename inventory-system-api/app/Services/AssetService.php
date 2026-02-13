<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\Asset;
use App\Helpers\IdGenerator;
use App\Traits\ValidatesLocationAccess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Auth, DB, Hash, Storage};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class AssetService
{
    use ValidatesLocationAccess;

    public function getAll(): Collection
    {
        return Asset::with(['category', 'location', 'images'])->get();
    }

    public function getAssetFilters(array $filters = []): Collection
    {
        $query = Asset::select('id_asset', 'asset_name', 'brand', 'condition', 'availability_status');
        if (isset($filters['location'])) {
            $query->where('id_location', $filters['location']);
        }
        return $query->get();

    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Asset::select('assets.*')->with(['category', 'location', 'images', 'recommendations.jobProfile']);
        $search = $filters['search'] ?? null;
        $jobProfile = $filters['job_profile'] ?? null;
        $category = $filters['category'] ?? null;
        $location = $filters['location'] ?? null;
        $condition = $filters['condition'] ?? null;
        $availability = $filters['availability'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($jobProfile) {
            $query->whereHas('recommendations', function ($q) use ($jobProfile) {
                $q->whereIn('id_job_profile', [$jobProfile, 1]);
            });
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id_asset', 'ilike', "%{$search}%")
                ->orWhere('asset_name', 'ilike', "%{$search}%")
                ->orWhere('brand', 'ilike', "%{$search}%");
            });
        }
        if ($category) {
            $query->where('id_category', $category);
        }
        if ($location) {
            $query->where('id_location', $location);
        }
        if ($condition) {
            $query->where('condition', $condition);
        }
        if ($availability) {
            $query->where('availability_status', $availability);
        }
        $allowSortBy = ['id_asset', 'asset_name', 'brand', 'availability_status', 'condition', 'created_at', 'updated_at', 'category_name', 'location_name'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'category_name') {
            $query->join('categories', 'assets.id_category', '=', 'categories.id_category')
                  ->orderBy('categories.category_name', $sortDir);
        } elseif ($sortBy === 'location_name') {
            $query->join('locations', 'assets.id_location', '=', 'locations.id_location')
                  ->orderBy('locations.location_name', $sortDir);
        } else {
            $query->orderBy('assets.' . $sortBy, $sortDir);
        }
        return $query->paginate($perPage);
    }

    public function filterBySpecifications(array $specs, int $perPage = 10): LengthAwarePaginator
    {
        $query = Asset::query();
        foreach ($specs as $key => $criteria) {
            $jsonKey = preg_replace('/[^a-zA-Z0-9 ]/', '', $key);
            if (is_array($criteria)) {
                if (isset($criteria['min']) || isset($criteria['max'])) {
                    $min = $criteria['min'] ?? 0;
                    $max = $criteria['max'] ?? 99999999;
                    $rawJsonValue = "CAST(specification->'{$jsonKey}'->>'value' AS INTEGER)";
                    $query->whereRaw("$rawJsonValue >= ?", [$min]);
                    if (isset($criteria['max'])) {
                        $query->whereRaw("$rawJsonValue <= ?", [$max]);
                    }
                }
                if (isset($criteria['exists']) && filter_var($criteria['exists'], FILTER_VALIDATE_BOOLEAN)) {
                    $query->whereRaw("specification ? '{$jsonKey}'");
                }
            } else {
                $value = $criteria;
                $query->where(function ($q) use ($jsonKey, $value) {
                    $q->whereRaw("specification->'{$jsonKey}'->>'name' ILIKE ?", ["%{$value}%"])
                      ->orWhereRaw("specification->'{$jsonKey}'->>'value' ILIKE ?", ["%{$value}%"]);
                });
            }
        }
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Asset
    {
        return Asset::find($id);
    }

    public function findDetail(string $id): ?Asset
    {
        return Asset::with([
            'category',
            'location',
            'images',
            'loans' => fn($q) => $q->with('user')->latest()->limit(10),
            'maintenances' => fn($q) => $q->with('maintenanceOfficer')->latest()->limit(10),
            'recommendations.jobProfile'
        ])->find($id);
    }

    public function suggestId(string $categoryId): array
    {
        return IdGenerator::suggestAssetId($categoryId);
    }

    public function create(array $data, ?array $images = null): Asset
    {
        $this->ensureUserCanManageLocation($data['id_location']);
        $asset = DB::transaction(function () use ($data, $images) {
            $prefix = $data['id_category'];
            $assetId = $data['id_asset'] ?? IdGenerator::generateAssetId($prefix);
            if (Asset::where('id_asset', $assetId)->exists()) {
                throw new \Exception('ID asset telah digunakan. Silakan coba lagi.');
            }
            if (!str_starts_with($assetId, $prefix . '-')) {
                throw new \Exception("ID Asset harus diawali dengan prefix kategori: {$prefix}-XXXX");
            }
            $assetData = array_merge($data, ['id_asset' => $assetId]);
            $asset = Asset::create($assetData);
            if (!empty($images)) {
                $this->uploadImages($asset, $images, $data['image_description'] ?? []);
            }
            return $asset->load(['category', 'location', 'images']);
        });
        DataChanged::dispatch('assets', 'created', 'all');
        return $asset;
    }

    public function update(Asset $asset, array $data, ?array $images = null): Asset
    {
        $this->ensureUserCanManageLocation($asset->id_location);
        if (isset($data['id_location']) && $data['id_location'] !== $asset->id_location) {
            $this->ensureUserCanManageLocation($data['id_location']);
        }
        if (isset($data['id_category']) && $data['id_category'] !== $asset->id_category) {
            throw new \Exception('Tidak dapat mengubah kategori. ID aset terikat dengan kategori.');
        }
        $updatedAsset = DB::transaction(function () use ($asset, $data, $images) {
            $asset->update($data);
            $disk = config('filesystems.default');
            if (!empty($data['deleted_images'])) {
                $imagesToDelete = $asset->images()->whereIn('filename', $data['deleted_images'])->get();
                foreach ($imagesToDelete as $img) {
                    if ($img->image_url) {
                        if (Storage::disk($disk)->exists($img->image_url)) {
                            Storage::disk($disk)->delete($img->image_url);
                        }
                    }
                }
                $asset->images()->whereIn('filename', $data['deleted_images'])->delete();
            }
            if (!empty($data['existing_images'])) {
                foreach ($data['existing_images'] as $existing) {
                    if (isset($existing['id'])) {
                        $asset->images()
                            ->where('filename', $existing['filename'])
                            ->update(['description' => $existing['image_description'] ?? null]);
                    }
                }
            }
            if (!empty($images)) {
                $descriptions = $data['image_description'] ?? [];
                $this->uploadImages($asset, $images, $descriptions);
            }
            return $asset->fresh(['category', 'location', 'images']);
        });
        DataChanged::dispatch('assets', 'updated', 'all');
        return $updatedAsset;
    }

    public function delete(array $request, Asset $asset): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusAset321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        $hasActiveLoans = $asset->loans()
            ->whereIn('loan_status', ['Menunggu Konfirmasi Peminjaman', 'Dipinjam'])
            ->exists();
        if ($hasActiveLoans) {
            throw new \Exception('Tidak dapat menghapus aset yang masih dalam peminjaman aktif.');
        }
        DB::transaction(function () use ($asset) {
            $disk = config('filesystems.default');
            foreach ($asset->images as $image) {
                if (Storage::disk($disk)->exists($image->image_url)) {
                    Storage::disk($disk)->delete($image->image_url);
                }
            }
            $asset->delete();
        });
        DataChanged::dispatch('assets', 'deleted', 'all');
    }

    public function getStatistics(): array
    {
        $generalStats = Asset::toBase()
            ->selectRaw("
                COUNT(*) as total_assets,
                SUM(CASE WHEN availability_status = 'Tersedia' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN availability_status = 'Dipinjam' THEN 1 ELSE 0 END) as borrowed,
                SUM(CASE WHEN availability_status = 'Dalam Perbaikan' THEN 1 ELSE 0 END) as in_maintenance,
                SUM(CASE WHEN \"condition\" = 'Baik' THEN 1 ELSE 0 END) as good_condition,
                SUM(CASE WHEN \"condition\" = 'Rusak' THEN 1 ELSE 0 END) as damaged,
                SUM(CASE WHEN \"condition\" = 'Hilang' THEN 1 ELSE 0 END) as lost
            ")
            ->first();
        $byCategory = Asset::select('id_category')
            ->selectRaw('COUNT(*) as count')
            ->with('category:id_category,category_name')
            ->groupBy('id_category')
            ->orderByDesc('count')
            ->get();
        $byLocation = Asset::select('id_location')
            ->selectRaw('COUNT(*) as count')
            ->with('location:id_location,location_name,building')
            ->groupBy('id_location')
            ->orderByDesc('count')
            ->get();
        return [
            'total_assets'   => (int) $generalStats->total_assets,
            'available'      => (int) $generalStats->available,
            'borrowed'       => (int) $generalStats->borrowed,
            'in_maintenance' => (int) $generalStats->in_maintenance,
            'good_condition' => (int) $generalStats->good_condition,
            'damaged'        => (int) $generalStats->damaged,
            'lost'           => (int) $generalStats->lost,
            'by_category'    => $byCategory,
            'by_location'    => $byLocation,
        ];
    }

    private function uploadImages(Asset $asset, array $images, ?array $descriptions): void
    {
        $this->ensureUserCanManageLocation($asset->id_location);
        $disk = config('filesystems.default');
        $visibility = ($disk === 'public') ? 'public' : 'private';
        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $extension = $image->getClientOriginalExtension();
                $fileName = IdGenerator::generateImageFileName($asset, $extension);
                $folderPath = 'InventoryAPI/asset-images/' . $asset->id_asset;
                try {
                    $path = Storage::disk($disk)->putFileAs(
                        $folderPath,
                        $image,
                        $fileName,
                        $visibility
                    );
                    if ($path) {
                        $asset->images()->create([
                            'filename'    => $fileName,
                            'image_url'   => $path,
                            'description' => $descriptions[$index] ?? null,
                        ]);
                    } else {
                        Log::error("Gagal upload file ke disk $disk: $fileName");
                    }
                } catch (\Throwable $e) {
                    Log::error("Upload Error ($disk): " . $e->getMessage());
                }
            }
        }
    }
}
