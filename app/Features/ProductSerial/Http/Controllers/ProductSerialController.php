<?php

namespace App\Features\ProductSerial\Http\Controllers;

use App\Features\ProductSerial\Http\Resources\ProductSerialResource;
use App\Features\ProductSerial\Services\ProductSerialService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSerialController extends Controller
{
    public function __construct(
        protected ProductSerialService $serialService
    ) {}

    /**
     * Tampilkan daftar serial number terpaginasi dengan filter.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'product_id',
            'location_id',
            'store_id',
            'status',
            'condition',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 5), 100);

        $serials = $this->serialService->paginateSerials($filters, $perPage);

        return ApiResponse::success(
            data: ProductSerialResource::collection($serials),
            message: 'Daftar serial number berhasil dimuat.',
            meta: [
                'current_page' => $serials->currentPage(),
                'last_page' => $serials->lastPage(),
                'per_page' => $serials->perPage(),
                'total' => $serials->total(),
                'from' => $serials->firstItem(),
                'to' => $serials->lastItem(),
            ],
        );
    }

    /**
     * Tampilkan detail serial number spesifik beserta riwayat timeline movement.
     */
    public function show(string $idOrNumber): JsonResponse
    {
        $serial = $this->serialService->getSerialWithHistory($idOrNumber);

        if (! $serial) {
            return ApiResponse::error(
                message: 'Serial number tidak ditemukan dalam sistem.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: new ProductSerialResource($serial),
            message: 'Detail serial number berhasil dimuat.'
        );
    }

    /**
     * Endpoint lookup cepat untuk pemindaian scanner barcode/QR.
     */
    public function lookup(Request $request): JsonResponse
    {
        $sn = (string) $request->input('sn', $request->input('serial_number', ''));

        if (trim($sn) === '') {
            return ApiResponse::error(
                message: 'Parameter serial number (sn) wajib diisi.',
                status: 422
            );
        }

        $serial = $this->serialService->getSerialWithHistory($sn);

        if (! $serial) {
            return ApiResponse::error(
                message: "Serial number '{$sn}' belum pernah terdaftar di sistem.",
                status: 404
            );
        }

        return ApiResponse::success(
            data: new ProductSerialResource($serial),
            message: 'Data serial number berhasil ditemukan.'
        );
    }
}
