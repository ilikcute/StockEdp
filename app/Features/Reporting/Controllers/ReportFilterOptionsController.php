<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Models\User;
use App\Features\Reporting\Requests\ReportProductFilterOptionsRequest;
use App\Features\Reporting\Requests\ReportSupplierFilterOptionsRequest;
use App\Features\Reporting\Services\ReportFilterOptionsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportFilterOptionsController extends Controller
{
    public function __construct(
        protected ReportFilterOptionsService $service
    ) {}

    public function baseOptions(Request $request): JsonResponse
    {
        $this->authorizeAnyReportPermission($request->user());

        $allowedLocationIds = $request->user()->getAllowedLocationIds();
        $options = $this->service->getBaseOptions($allowedLocationIds);

        return response()->json([
            'data' => $options,
        ]);
    }

    public function productOptions(ReportProductFilterOptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 20);

        $products = $this->service->getProductOptions($search, $perPage);

        return response()->json([
            'data' => $products,
        ]);
    }

    public function supplierOptions(ReportSupplierFilterOptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 20);

        $suppliers = $this->service->getSupplierOptions($search, $perPage);

        return response()->json([
            'data' => $suppliers,
        ]);
    }

    protected function authorizeAnyReportPermission(?User $user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $hasPermission = $user->can(PermissionCode::REPORTS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value)
            || $user->can(PermissionCode::REPORTS_STOCK_CARD_VIEW->value)
            || $user->can(PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value)
            || $user->can(PermissionCode::REPORTS_LOW_STOCK_VIEW->value);

        if (! $hasPermission) {
            abort(403, 'Akses laporan tidak diizinkan.');
        }
    }
}
