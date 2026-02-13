<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Models\Category;
use App\Helpers\IdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CategoryService
{
    public function getAll(): Collection
    {
        return Category::select('id_category', 'category_name')->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Category::query();
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('category_name', 'ilike', "%{$search}%")
                ->orWhere('id_category', 'ilike', "%{$search}%");
            });
        }
        $allowSortBy = ['id_category', 'category_name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);
        return $query->paginate($perPage);
    }

    public function find(string $id): ?Category
    {
        return Category::find($id);
    }

    public function findWithAssets(string $id)
    {
        return Category::find($id)?->assets()
            ->select('id_asset', 'asset_name', 'brand', 'condition', 'availability_status')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function suggestId(string $categoryName): array
    {
        return IdGenerator::suggestCategoryId($categoryName);
    }

    public function create(array $data): Category
    {
        $category = DB::transaction(function () use ($data) {
            $categoryId = $data['id_category'] ?? IdGenerator::generateCategoryId($data['category_name']);
            if (Category::where('id_category', $categoryId)->exists()) {
                throw new \Exception('ID kategori telah digunakan. Silakan coba lagi.');
            }
            $categoryData = array_merge($data, ['id_category' => $categoryId]);
            $category = Category::create($categoryData);
            return $category->fresh();
        });
        DataChanged::dispatch('categories', 'created', 'all');
        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $updatedCategory = DB::transaction(function () use ($category, $data) {
            $category->update($data);
            return $category->fresh();
        });
        DataChanged::dispatch('categories', 'updated', 'all');
        return $updatedCategory;
    }

    public function delete(array $request, Category $category): void
    {
        $user = Auth::user();
        if (!Hash::check($request['account_password'], $user->password)) {
            throw new \Exception('Password akun tidak valid.');
        }
        if ($request['delete_password'] !== 'HapusKategori321') {
            throw new \Exception('Password khusus penghapusan tidak valid.');
        }
        if ($category->assets()->count() > 0) {
            throw new \Exception('Tidak dapat menghapus kategori yang masih memiliki aset.');
        }
        DB::transaction(function () use ($category) {
            $category->delete();
        });
        DataChanged::dispatch('categories', 'deleted', 'all');
    }

    public function getStatistics(): array
    {
        return [
            'total_categories' => Category::count(),
            'with_assets' => Category::has('assets')->count(),
            'without_assets' => Category::doesntHave('assets')->count(),
            'assets_per_category' => Category::withCount('assets')
                ->orderByDesc('assets_count')
                ->get()
                ->map(fn($cat) => [
                    'id' => $cat->id_category,
                    'name' => $cat->category_name,
                    'asset_count' => $cat->assets_count,
                ]),
        ];
    }
}
