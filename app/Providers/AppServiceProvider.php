<?php

namespace App\Providers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Features\Category\Repositories\Eloquent\CategoryRepository;
use App\Features\Dashboard\Repositories\Contracts\OperationalDashboardRepositoryInterface;
use App\Features\Dashboard\Repositories\Eloquent\OperationalDashboardRepository;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockIssueRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockOpnameRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockReceiptRepositoryInterface;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Features\Inventory\Repositories\Eloquent\InventoryBalanceRepository;
use App\Features\Inventory\Repositories\Eloquent\StockAdjustmentRepository;
use App\Features\Inventory\Repositories\Eloquent\StockIssueRepository;
use App\Features\Inventory\Repositories\Eloquent\StockMovementRepository;
use App\Features\Inventory\Repositories\Eloquent\StockOpnameRepository;
use App\Features\Inventory\Repositories\Eloquent\StockReceiptRepository;
use App\Features\Inventory\Repositories\Eloquent\StockTransferRepository;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;
use App\Features\Location\Repositories\Eloquent\LocationRepository;
use App\Features\MasterDataImport\Contracts\MasterDataImportReaderInterface;
use App\Features\MasterDataImport\Readers\CsvMasterDataImportReader;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Features\Product\Repositories\Eloquent\ProductRepository;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use App\Features\Reporting\Repositories\Eloquent\ReportingRepository;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Features\Supplier\Repositories\Eloquent\SupplierRepository;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;
use App\Features\Unit\Repositories\Eloquent\UnitRepository;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OperationalDashboardRepositoryInterface::class, OperationalDashboardRepository::class);
        $this->app->bind(ReportingRepositoryInterface::class, ReportingRepository::class);

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );

        $this->app->bind(
            UnitRepositoryInterface::class,
            UnitRepository::class
        );

        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            InventoryBalanceRepositoryInterface::class,
            InventoryBalanceRepository::class
        );

        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockReceiptRepositoryInterface::class, StockReceiptRepository::class);
        $this->app->bind(StockIssueRepositoryInterface::class, StockIssueRepository::class);
        $this->app->bind(StockTransferRepositoryInterface::class, StockTransferRepository::class);
        $this->app->bind(StockAdjustmentRepositoryInterface::class, StockAdjustmentRepository::class);
        $this->app->bind(
            StockOpnameRepositoryInterface::class,
            StockOpnameRepository::class
        );

        $this->app->bind(
            MasterDataImportReaderInterface::class,
            CsvMasterDataImportReader::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom macro untuk API response
        Response::macro('api', function ($data = null, string $message = 'Success', int $status = 200) {
            return Response::json([
                'success' => $status >= 200 && $status < 300,
                'message' => $message,
                'data' => $data,
            ], $status);
        });

        // Hubungkan custom RBAC ke Gate Laravel
        Gate::before(function ($user, $ability) {
            // Bypass otorisasi jika user memiliki role ADMIN
            if (method_exists($user, 'hasRole') && $user->hasRole(RoleCode::ADMIN)) {
                return true;
            }
        });

        // Daftarkan Gate untuk setiap kode permission yang ada
        foreach (PermissionCode::cases() as $permission) {
            Gate::define($permission->value, function ($user) use ($permission) {
                return method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission);
            });
        }

        // Daftarkan Named API Rate Limiter
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(60)->by((string) $key)->response(function (Request $request, array $headers) {
                return ApiResponse::error(
                    message: 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
                    status: 429,
                )->withHeaders($headers);
            });
        });
    }
}
