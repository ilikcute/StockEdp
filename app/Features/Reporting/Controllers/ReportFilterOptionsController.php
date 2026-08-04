<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Category\Models\Category;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportFilterOptionsController extends Controller
{
    public function baseOptions(Request $request): JsonResponse
    {
        $this->authorizeAnyReportPermission($request->user());

        $allowedLocationIds = $request->user()->getAllowedLocationIds();

        $locations = Location::query()
            ->when(! empty($allowedLocationIds), fn ($q) => $q->whereIn('id', $allowedLocationIds))
            ->when(empty($allowedLocationIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);

        return response()->json([
            'data' => [
                'locations' => $locations,
                'categories' => $categories,
                'units' => $units,
            ],
        ]);
    }

    public function productOptions(Request $request): JsonResponse
    {
        $this->authorizeAnyReportPermission($request->user());

        $search = trim((string) $request->input('search', ''));
        $perPage = min((int) $request->input('per_page', 20), 50);

        $products = Product::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($perPage)
            ->get(['id', 'name', 'sku']);

        return response()->json([
            'data' => $products,
        ]);
    }

    public function supplierOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $hasPermission = $user->can(PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value)
            || $user->can(PermissionCode::REPORTS_VIEW->value)
            || $user->can(PermissionCode::SUPPLIERS_VIEW->value);

        if (! $hasPermission) {
            abort(403, 'Akses daftar supplier tidak diizinkan.');
        }

        $search = trim((string) $request->input('search', ''));
        $perPage = min((int) $request->input('per_page', 20), 50);

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($perPage)
            ->get(['id', 'name', 'code']);

        return response()->json([
            'data' => $suppliers,
        ]);
    }

    protected function authorizeAnyReportPermission($user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $reportPermissions = [
            PermissionCode::REPORTS_VIEW->value,
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
        ];

        foreach ($reportPermissions as $perm) {
            if ($user->can($perm)) {
                return;
            }
        }

        abort(403, 'Akses filter laporan tidak diizinkan.');
    }
}
