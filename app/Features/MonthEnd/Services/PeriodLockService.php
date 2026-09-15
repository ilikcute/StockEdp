<?php

namespace App\Features\MonthEnd\Services;

use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use DateTimeInterface;

class PeriodLockService
{
    /**
     * Pastikan tanggal transaksi berada dalam periode yang berstatus OPEN.
     * Jika periode ditutup (CLOSED), lemparkan DomainException.
     *
     * @throws DomainException
     */
    public function ensureDateIsOpen(string|DateTimeInterface|null $date, string $actionDescription = ''): void
    {
        if (empty($date)) {
            return;
        }

        $dateObj = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $dateStr = $dateObj->toDateString();

        $closedPeriod = InventoryPeriod::query()
            ->where('status', PeriodStatus::CLOSED->value)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($closedPeriod) {
            $monthName = Carbon::createFromDate($closedPeriod->year, $closedPeriod->month, 1)
                ->locale('id')
                ->translatedFormat('F Y');

            $formattedDate = $dateObj->locale('id')->translatedFormat('d/m/Y');

            $context = $actionDescription ? " untuk {$actionDescription}" : '';

            $message = "Periode transaksi tanggal {$formattedDate} ({$monthName}) telah ditutup buku (CLOSED). "
                ."Tindakan{$context} tidak diizinkan pada periode yang telah dikunci.";

            throw new DomainException($message, 422, [
                'date' => [$message],
                'period_key' => [$closedPeriod->period_key],
            ]);
        }
    }

    /**
     * Memeriksa apakah suatu tanggal berada pada periode yang tertutup.
     */
    public function isDateClosed(string|DateTimeInterface|null $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->toDateString();

        return InventoryPeriod::query()
            ->where('status', PeriodStatus::CLOSED->value)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->exists();
    }

    /**
     * Dapatkan periode tertutup untuk tanggal tertentu bila ada.
     */
    public function getClosedPeriodForDate(string|DateTimeInterface|null $date): ?InventoryPeriod
    {
        if (empty($date)) {
            return null;
        }

        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->toDateString();

        return InventoryPeriod::query()
            ->where('status', PeriodStatus::CLOSED->value)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();
    }
}
