<?php

namespace App\Features\Rma\Http\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Rma\Actions\CancelVendorRmaAction;
use App\Features\Rma\Actions\CompleteVendorRmaAction;
use App\Features\Rma\Actions\CreateVendorRmaAction;
use App\Features\Rma\Actions\DispatchVendorRmaAction;
use App\Features\Rma\Http\Requests\StoreVendorRmaRequest;
use App\Features\Rma\Http\Resources\VendorRmaResource;
use App\Features\Rma\Models\VendorRma;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorRmaController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_VIEW->value);

        $query = VendorRma::query()->with(['supplier', 'originLocation', 'creator']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('rma_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 5), 100);

        $rmas = $query->orderByDesc('id')->paginate($perPage);

        return ApiResponse::success(
            data: VendorRmaResource::collection($rmas),
            message: 'Daftar dokumen RMA berhasil dimuat.',
            meta: [
                'current_page' => $rmas->currentPage(),
                'last_page' => $rmas->lastPage(),
                'per_page' => $rmas->perPage(),
                'total' => $rmas->total(),
                'from' => $rmas->firstItem(),
                'to' => $rmas->lastItem(),
            ]
        );
    }

    public function store(StoreVendorRmaRequest $request, CreateVendorRmaAction $action): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_MANAGE->value);

        $rma = $action->execute($request->validated(), Auth::id());

        return ApiResponse::success(
            data: new VendorRmaResource($rma),
            message: 'Draft dokumen RMA vendor berhasil dibuat.',
            status: 201
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_VIEW->value);

        $rma = VendorRma::with([
            'supplier',
            'originLocation',
            'items.product.unit',
            'items.productSerial',
            'creator',
        ])->find($id);

        if (! $rma) {
            return ApiResponse::error(
                message: 'Dokumen RMA tidak ditemukan.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: new VendorRmaResource($rma),
            message: 'Detail dokumen RMA berhasil dimuat.'
        );
    }

    public function dispatch(int $id, DispatchVendorRmaAction $action): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_MANAGE->value);

        $rma = VendorRma::findOrFail($id);
        $dispatched = $action->execute($rma, Auth::id());

        return ApiResponse::success(
            data: new VendorRmaResource($dispatched),
            message: 'Dokumen RMA berhasil dikirim ke vendor. Stok rusak telah dikurangkan dan serial tercatat RETURNED_TO_VENDOR.'
        );
    }

    public function complete(int $id, Request $request, CompleteVendorRmaAction $action): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_MANAGE->value);

        $data = $request->validate([
            'destination_location_id' => 'nullable|integer|exists:locations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $rma = VendorRma::findOrFail($id);
        $completed = $action->execute($rma, $data, Auth::id());

        return ApiResponse::success(
            data: new VendorRmaResource($completed),
            message: 'Pengembalian RMA berhasil diterima kembali ke gudang sebagai stok bagus (GOOD).'
        );
    }

    public function cancel(int $id, Request $request, CancelVendorRmaAction $action): JsonResponse
    {
        $this->authorize(PermissionCode::VENDOR_RMAS_MANAGE->value);

        $rma = VendorRma::findOrFail($id);
        $cancelled = $action->execute($rma, $request->input('reason'));

        return ApiResponse::success(
            data: new VendorRmaResource($cancelled),
            message: 'Dokumen RMA berhasil dibatalkan.'
        );
    }
}
