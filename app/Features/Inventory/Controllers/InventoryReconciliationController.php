<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Inventory\Services\InventoryReconciliationService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReconciliationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly InventoryReconciliationService $service
    ) {}

    /**
     * Pindai dan temukan selisih antara saldo fisik (inventory_balances)
     * dan buku besar mutasi transaksi (stock_movements).
     */
    public function scan(Request $request): JsonResponse
    {
        $this->authorize(PermissionCode::INVENTORY_RECONCILE->value);

        $locationId = $request->filled('location_id') ? (int) $request->input('location_id') : null;
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $condition = $request->filled('condition') ? (string) $request->input('condition') : null;

        $result = $this->service->scanDiscrepancies($locationId, $productId, $condition);

        return ApiResponse::success(
            $result,
            $result['summary']['status'] === 'SYNCED'
                ? 'Seluruh saldo fisik dan mutasi transaksi dalam kondisi sinkron.'
                : 'Ditemukan '.$result['summary']['discrepant_pairs'].' selisih saldo persediaan.'
        );
    }

    /**
     * Hitung ulang dan sinkronkan saldo inventory_balances berdasarkan mutasi transaksi.
     */
    public function apply(Request $request): JsonResponse
    {
        $this->authorize(PermissionCode::INVENTORY_RECONCILE->value);

        $keys = $request->input('keys', []);
        if (! is_array($keys)) {
            $keys = [];
        }

        $result = $this->service->reconcileBalances($keys, (int) $request->user()->id);

        return ApiResponse::success(
            $result,
            $result['message']
        );
    }
}
