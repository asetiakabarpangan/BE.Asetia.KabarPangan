<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\{StoreCategoryRequest, UpdateCategoryRequest};
use App\Services\CategoryService;
use Illuminate\Http\{Request, JsonResponse};

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = [
            'search'  => $request->get('search'),
            'sortBy'  => $request->get('sort_by'),
            'sortDir' => $request->get('sort_dir'),
        ];
        $categories = $this->categoryService->getPaginated($filters, $perPage);
        return $this->success($categories);
    }

    public function all(): JsonResponse
    {
        $categories = $this->categoryService->getAll();
        return $this->success($categories);
    }

    public function suggestId(Request $request): JsonResponse
    {
        $request->validate(['category_name' => 'required|string']);
        $suggestion = $this->categoryService->suggestId($request->category_name);
        return $this->success($suggestion);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());
            return $this->success($category, 'Kategori berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryService->find($id);
        if (!$category) {
            return $this->notFound('Kategori tidak ditemukan.');
        }
        $data = [
            'category' => $category,
            'assets' => $this->categoryService->findWithAssets($id),
            'assets_count' => $category->assets()->count(),
        ];
        return $this->success($data);
    }

    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $category = $this->categoryService->find($id);
        if (!$category) {
            return $this->notFound('Kategori tidak ditemukan.');
        }
        try {
            $updated = $this->categoryService->update($category, $request->validated());
            return $this->success($updated, 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $category = $this->categoryService->find($id);
        if (!$category) {
            return $this->notFound('Kategori tidak ditemukan.');
        }
        try {
            $this->categoryService->delete($request->all(), $category);
            return $this->success([], 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->categoryService->getStatistics();
        return $this->success($stats);
    }
}
