<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Actions\AddUnexpectedProductAction;
use App\Features\Inventory\Actions\CancelStockOpnameAction;
use App\Features\Inventory\Actions\CompleteStockOpnameAction;
use App\Features\Inventory\Actions\CreateStockOpnameAction;
use App\Features\Inventory\Actions\InputCountAction;
use App\Features\Inventory\Actions\PostStockOpnameAction;
use App\Features\Inventory\Actions\ReopenStockOpnameAction;
use App\Features\Inventory\Actions\StartStockOpnameAction;
use App\Features\Inventory\Actions\UpdateStockOpnameAction;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Repositories\Contracts\StockOpnameRepositoryInterface;
use App\Features\Inventory\Requests\AddUnexpectedProductRequest;
use App\Features\Inventory\Requests\CancelStockOpnameRequest;
use App\Features\Inventory\Requests\CreateStockOpnameRequest;
use App\Features\Inventory\Requests\InputCountRequest;
use App\Features\Inventory\Requests\ReopenStockOpnameRequest;
use App\Features\Inventory\Requests\UpdateStockOpnameRequest;
use App\Features\Inventory\Resources\StockOpnameItemResource;
use App\Features\Inventory\Resources\StockOpnameResource;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StockOpnameRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockOpname::class);

        $allowedLocations = $request->user() ? $request->user()->getAllowedLocationIds() : [];

        $filters = $request->only([
            'status',
            'location_id',
            'start_date',
            'end_date',
            'search',
        ]);

        $opnames = $this->repository->getPaginatedOpnames(
            $allowedLocations,
            $filters,
            $request->input('sort_field', 'id'),
            $request->input('sort_direction', 'desc'),
            (int) $request->input('per_page', 15)
        );

        return response()->api(
            StockOpnameResource::collection($opnames)->response()->getData(true)
        );
    }

    public function store(
        CreateStockOpnameRequest $request,
        CreateStockOpnameAction $action
    ): JsonResponse {
        $this->authorize('create', StockOpname::class);

        $opname = $action->execute($request->validated(), $request->user()->id);

        return response()->api(new StockOpnameResource($opname), 'Draft stock opname berhasil dibuat.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('view', $opname);

        return response()->api(new StockOpnameResource($opname));
    }

    public function update(
        UpdateStockOpnameRequest $request,
        int $id,
        UpdateStockOpnameAction $action
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('update', $opname);

        $updatedOpname = $action->execute($opname, $request->validated(), $request->user()->id);

        return response()->api(new StockOpnameResource($updatedOpname), 'Draft stock opname berhasil diperbarui.');
    }

    public function start(
        int $id,
        StartStockOpnameAction $action,
        Request $request
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('start', $opname);

        $startedOpname = $action->execute($opname, $request->user()->id);

        return response()->api(new StockOpnameResource($startedOpname), 'Stock opname berhasil dimulai dan lokasi persediaan telah dibekukan.');
    }

    public function count(
        InputCountRequest $request,
        int $id,
        int $itemId,
        InputCountAction $action
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('count', $opname);

        $item = $action->execute($opname, $itemId, $request->validated(), $request->user()->id);

        return response()->api(new StockOpnameItemResource($item), 'Kuantitas fisik item berhasil disimpan.');
    }

    public function addUnexpected(
        AddUnexpectedProductRequest $request,
        int $id,
        AddUnexpectedProductAction $action
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('addUnexpected', $opname);

        $item = $action->execute($opname, $request->validated(), $request->user()->id);

        return response()->api(new StockOpnameItemResource($item), 'Produk tak terduga berhasil ditambahkan ke sesi opname.', 201);
    }

    public function complete(
        int $id,
        CompleteStockOpnameAction $action,
        Request $request
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('complete', $opname);

        $completedOpname = $action->execute($opname, $request->user()->id);

        return response()->api(new StockOpnameResource($completedOpname), 'Perhitungan stock opname telah selesai.');
    }

    public function reopen(
        ReopenStockOpnameRequest $request,
        int $id,
        ReopenStockOpnameAction $action
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('reopen', $opname);

        $reopenedOpname = $action->execute($opname, $request->input('reason'), $request->user()->id);

        return response()->api(new StockOpnameResource($reopenedOpname), 'Sesi stock opname berhasil dibuka kembali untuk penghitungan ulang.');
    }

    public function post(
        int $id,
        PostStockOpnameAction $action,
        Request $request
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('post', $opname);

        $postedOpname = $action->execute($opname, $request->user()->id);

        return response()->api(new StockOpnameResource($postedOpname), 'Rekonsiliasi stock opname berhasil diposting dan lokasi persediaan telah dibuka.');
    }

    public function cancel(
        CancelStockOpnameRequest $request,
        int $id,
        CancelStockOpnameAction $action
    ): JsonResponse {
        $opname = $this->repository->findById($id);

        if (! $opname) {
            return response()->api(null, 'Dokumen stock opname tidak ditemukan.', 404);
        }

        $this->authorize('cancel', $opname);

        $canceledOpname = $action->execute($opname, $request->input('cancel_reason'), $request->user()->id);

        return response()->api(new StockOpnameResource($canceledOpname), 'Dokumen stock opname berhasil dibatalkan dan lokasi persediaan telah dibuka.');
    }
}
