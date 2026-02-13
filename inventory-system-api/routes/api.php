<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataPortController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
Route::post('/email/resend', [AuthController::class, 'resendVerification']);

// ALL (for dropdown)
Route::get('/departments/all', [DepartmentController::class, 'all']);
Route::get('/job-profiles/all', [JobProfileController::class, 'all']);

/*
|--------------------------------------------------------------------------
| 2. PROTECTED ROUTES (EMPLOYEE + MODERATOR + ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'verified', 'token-expiry'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | GROUP A: PERSONAL ACTIONS (Semua User)
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateMyProfile']);
    Route::put('/change-password', [AuthController::class, 'changeMyPassword']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll']);

    // My History
    Route::prefix('my')->group(function () {
        Route::get('/loans', [LoanController::class, 'myHistory']);
        Route::get('/procurements', [ProcurementController::class, 'myHistory']);
        Route::get('/recommendations', [RecommendationController::class, 'myAvailableAssets']);
    });

    /*
    |--------------------------------------------------------------------------
    | GROUP B: MASTER DATA (READ ONLY) — EMPLOYEE, MODERATOR, ADMIN
    |--------------------------------------------------------------------------
    */
    // ALL (for dropdown)
    Route::get('/categories/all', [CategoryController::class, 'all']);
    Route::get('/locations/all', [LocationController::class, 'all']);

    // Paginated public data
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('locations', LocationController::class)->only(['index', 'show']);
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('job-profiles', JobProfileController::class)->only(['index', 'show']);

    // Other Read Utilities
    Route::post('/assets/search-by-spec', [AssetController::class, 'searchBySpec']);
    Route::get('/job-profiles/{id}/recommended-assets', [JobProfileController::class, 'getRecommendedAssets']);
    Route::get('/recommendations/available-for/{userId}', [RecommendationController::class, 'getAvailableForUser']);

    // Statistics
    Route::prefix('statistics')->group(function () {
        Route::get('/assets', [AssetController::class, 'statistics']);
        Route::get('/recommendations', [RecommendationController::class, 'statistics']);
    });

    /*
    |--------------------------------------------------------------------------
    | GROUP C: EMPLOYEE ACTIONS (Peminjaman, Pengadaan)
    |--------------------------------------------------------------------------
    */
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/{id}', [AssetController::class, 'show']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{id}', [LoanController::class, 'show']);
    Route::put('/loans/{id}', [LoanController::class, 'update']);
    Route::delete('/loans/{id}/cancel', [LoanController::class, 'cancel']);
    Route::post('/loans/{id}/request-return', [LoanController::class, 'requestReturn']);

    Route::post('/procurements', [ProcurementController::class, 'store']);
    Route::get('/procurements/{id}', [ProcurementController::class, 'show']);
    Route::put('/procurements/{id}', [ProcurementController::class, 'update']);
    Route::delete('/procurements/{id}/cancel', [ProcurementController::class, 'cancel']);

    /*
    |--------------------------------------------------------------------------
    | GROUP D: MODERATOR + ADMIN (Operational & Asset Management)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin,moderator'])->group(function () {

        // Asset Management (Moderator only for own location)
        Route::post('/assets/suggest-id', [AssetController::class, 'suggestId']);
        Route::delete('/assets/{assetId}/images/{filename}', [AssetController::class, 'deleteImage'])
            ->where('filename', '.*');
        Route::apiResource('assets', AssetController::class)->except(['index', 'show', 'destroy']);
        Route::get('/assets-filtered', [AssetController::class, 'getAssetFilters']);
        Route::get('/users-filtered', [UserController::class, 'getUserFilters']);

        // Maintenance
        Route::post('/maintenances/{id}/complete', [MaintenanceController::class, 'complete']);
        Route::get('/maintenances/asset/{assetId}', [MaintenanceController::class, 'assetHistory']);
        Route::get('/maintenances/officer/{officerId}', [MaintenanceController::class, 'officerHistory']);
        Route::apiResource('maintenances', MaintenanceController::class)->except(['destroy']);

        // Loan Approval
        Route::get('/loans', [LoanController::class, 'index']);
        Route::post('/loans/{id}/approve', [LoanController::class, 'approve']);
        Route::post('/loans/{id}/reject', [LoanController::class, 'reject']);
        Route::post('/loans/{id}/confirm-return', [LoanController::class, 'confirmReturn']);

        // Recommendations
        Route::post('/recommendations/bulk', [RecommendationController::class, 'bulkInsert']);
        Route::get('/recommendations/job-profile/{jobProfileId}', [RecommendationController::class, 'getByJobProfile']);
        Route::delete('/recommendations/{assetId}/{jobProfileId}', [RecommendationController::class, 'destroy']);
        Route::apiResource('recommendations', RecommendationController::class)->only(['index', 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | GROUP E: ADMIN ONLY (Full CRUD on Everything)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        // Users
        Route::post('/users/suggest-id', [AuthController::class, 'suggestId']);
        Route::put('/profile/{id}', [AuthController::class, 'updateProfile']);
        Route::put('/change-password/{id}', [AuthController::class, 'changePassword']);
        Route::apiResource('users', UserController::class);

        // Monitoring User History
        Route::prefix('users/{id}')->group(function () {
            Route::get('/loans', [LoanController::class, 'userHistory']);
            Route::get('/procurements', [ProcurementController::class, 'userHistory']);
            Route::get('/maintenances', [MaintenanceController::class, 'officerHistory']);
        });

        // Master Data CRUD
        Route::post('/categories/suggest-id', [CategoryController::class, 'suggestId']);
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::post('/locations/suggest-id', [LocationController::class, 'suggestId']);
        Route::apiResource('locations', LocationController::class)->except(['index', 'show']);
        Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);
        Route::apiResource('job-profiles', JobProfileController::class)->except(['index', 'show']);
        Route::apiResource('assets', AssetController::class)->only(['destroy']);
        Route::apiResource('loans', LoanController::class)->only(['destroy']);
        Route::apiResource('maintenances', MaintenanceController::class)->only(['destroy']);

        // Procurement Approval
        Route::get('/procurements', [ProcurementController::class, 'index']);
        Route::post('/procurements/{id}/approve', [ProcurementController::class, 'approve']);
        Route::post('/procurements/{id}/reject', [ProcurementController::class, 'reject']);
        Route::post('/procurements/{id}/complete', [ProcurementController::class, 'complete']);
        Route::delete('/procurements/{id}', [ProcurementController::class, 'destroy']);

        // Statistics
        Route::prefix('statistics')->group(function () {
            Route::get('/users', [UserController::class, 'statistics']);
            Route::get('/loans', [LoanController::class, 'statistics']);
            Route::get('/maintenances', [MaintenanceController::class, 'statistics']);
            Route::get('/procurements', [ProcurementController::class, 'statistics']);
            Route::get('/categories', [CategoryController::class, 'statistics']);
            Route::get('/departments', [DepartmentController::class, 'statistics']);
            Route::get('/locations', [LocationController::class, 'statistics']);
            Route::get('/job-profiles', [JobProfileController::class, 'statistics']);
            Route::get('/histories', [DashboardController::class, 'getHistory']);
        });

        // Data Port
        Route::post('/export', [DataPortController::class, 'export']);
        Route::post('/import', [DataPortController::class, 'import']);

        // Activity Log
        Route::get('/logs', [ActivityLogController::class, 'index']);
    });
});

Route::get('/scheduler/run', function () {
    if (request()->query('key') !== env('SCHEDULER_KEY')) {
        return new JsonResponse(['message' => 'Unauthorized'], 403);
    }
    Artisan::call('schedule:run');
    return new JsonResponse(['message' => 'Scheduler executed', 'output' => Artisan::output()]);
});
