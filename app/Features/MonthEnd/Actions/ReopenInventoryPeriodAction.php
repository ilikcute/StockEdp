<?php

namespace App\Features\MonthEnd\Actions;

use App\Features\Audit\Services\ActivityLogger;
use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReopenInventoryPeriodAction
{
    /**
     * Membuka kembali periode persediaan yang telah ditutup.
     *
     * @param int $periodId
     * @param int $userId
     * @param string $reopenReason
     * @return InventoryPeriod
     * @throws DomainException
     */
    public function execute(int $periodId, int $userId, string $reopenReason): InventoryPeriod
    {
        $trimmedReason = trim($reopenReason);
        if (strlen($trimmedReason) < 5) {
            throw new DomainException('Alasan pembukaan kembali periode wajib diisi minimal 5 karakter.', 422);
        }

        /** @var InventoryPeriod|null $period */
        $period = InventoryPeriod::query()->find($periodId);
        if (! $period instanceof InventoryPeriod) {
            throw new DomainException('Periode persediaan tidak ditemukan.', 404);
        }

        if ($period->isOpen()) {
            throw new DomainException("Periode {$period->period_key} sudah dalam status TERBUKA (OPEN).", 422);
        }

        return DB::transaction(function () use ($period, $userId, $trimmedReason) {
            $period->status = PeriodStatus::OPEN;
            $period->reopened_at = Carbon::now();
            $period->reopened_by = $userId;
            $period->reopen_reason = $trimmedReason;
            $period->save();

            app(ActivityLogger::class)->record(
                module: 'month_end',
                action: 'reopen',
                description: "Membuka kembali periode persediaan {$period->period_key} dengan alasan: {$trimmedReason}",
                subject: $period,
                properties: [
                    'period_key' => $period->period_key,
                    'reason' => $trimmedReason,
                ],
                userId: $userId
            );

            return $period->load(['closedByUser', 'reopenedByUser']);
        });
    }
}
