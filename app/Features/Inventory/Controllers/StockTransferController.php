<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Actions\CancelStockTransferAction;
use App\Features\Inventory\Actions\CreateStockTransferAction;
use App\Features\Inventory\Actions\ReceiveStockTransferAction;
use App\Features\Inventory\Actions\SendStockTransferAction;
use App\Features\Inventory\Actions\UpdateStockTransferAction;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Features\Inventory\Requests\CreateStockTransferRequest;
use App\Features\Inventory\Requests\UpdateStockTransferRequest;
use App\Features\Inventory\Resources\StockTransferResource;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StockTransferRepositoryInterface $transferRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockTransfer::class);

        $filters = $request->only(['status', 'search', 'start_date', 'end_date']);
        $transfers = $this->transferRepository->getPaginatedTransfers(
            $filters,
            $request->input('sort_field', 'id'),
            $request->input('sort_direction', 'desc'),
            $request->input('per_page', 15)
        );

        return response()->api(
            StockTransferResource::collection($transfers)->response()->getData(true)
        );
    }

    public function store(
        CreateStockTransferRequest $request,
        CreateStockTransferAction $action
    ): JsonResponse {
        $this->authorize('create', StockTransfer::class);

        $transfer = $action->execute($request->validated(), $request->user()->id);

        return response()->api(new StockTransferResource($transfer), 'Transfer stok berhasil dibuat.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $transfer = $this->transferRepository->findById($id);

        if (! $transfer) {
            return response()->api(null, 'Transfer stok tidak ditemukan.', 404);
        }

        $this->authorize('view', $transfer);

        return response()->api(new StockTransferResource($transfer));
    }

    public function update(
        UpdateStockTransferRequest $request,
        int $id,
        UpdateStockTransferAction $action
    ): JsonResponse {
        $transfer = $this->transferRepository->findById($id);

        if (! $transfer) {
            return response()->api(null, 'Transfer stok tidak ditemukan.', 404);
        }

        $this->authorize('update', $transfer);

        $updatedTransfer = $action->execute($transfer, $request->validated(), $request->user()->id);

        return response()->api(new StockTransferResource($updatedTransfer), 'Transfer stok berhasil diperbarui.');
    }

    public function send(
        int $id,
        SendStockTransferAction $action,
        Request $request
    ): JsonResponse {
        $transfer = $this->transferRepository->findById($id);

        if (! $transfer) {
            return response()->api(null, 'Transfer stok tidak ditemukan.', 404);
        }

        $this->authorize('send', $transfer);

        $sentTransfer = $action->execute($transfer, $request->user()->id);

        return response()->api(new StockTransferResource($sentTransfer), 'Transfer stok berhasil dikirim.');
    }

    public function receive(
        int $id,
        ReceiveStockTransferAction $action,
        Request $request
    ): JsonResponse {
        $transfer = $this->transferRepository->findById($id);

        if (! $transfer) {
            return response()->api(null, 'Transfer stok tidak ditemukan.', 404);
        }

        $this->authorize('receive', $transfer);

        $receivedTransfer = $action->execute($transfer, $request->user()->id);

        return response()->api(new StockTransferResource($receivedTransfer), 'Transfer stok berhasil diterima.');
    }

    public function cancel(
        int $id,
        CancelStockTransferAction $action,
        Request $request
    ): JsonResponse {
        $transfer = $this->transferRepository->findById($id);

        if (! $transfer) {
            return response()->api(null, 'Transfer stok tidak ditemukan.', 404);
        }

        $this->authorize('cancel', $transfer);

        $canceledTransfer = $action->execute($transfer, $request->user()->id);

        return response()->api(new StockTransferResource($canceledTransfer), 'Transfer stok berhasil dibatalkan.');
    }
}
