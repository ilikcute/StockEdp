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

    /**
     * Dapatkan informasi peringatan jika periode aktif mendekati batas akhir penutupan (H-3).
     *
     * @param int $daysThreshold Default 3 hari
     * @return array|null Info periode mendekati closing atau null jika tidak ada
     */
    public function getApproachingClosingAlert(int $daysThreshold = 3): ?array
    {
        $today = Carbon::today();

        $openPeriod = InventoryPeriod::query()
            ->where('status', PeriodStatus::OPEN->value)
            ->where('end_date', '>=', $today->toDateString())
            ->orderBy('end_date', 'asc')
            ->first();

        if (! $openPeriod) {
            return null;
        }

        $endDate = Carbon::parse($openPeriod->end_date);
        $diffInDays = (int) $today->diffInDays($endDate, false);

        if ($diffInDays >= 0 && $diffInDays <= $daysThreshold) {
            $formattedEndDate = $openPeriod->end_date instanceof Carbon ? $openPeriod->end_date->format('d/m/Y') : Carbon::parse($openPeriod->end_date)->format('d/m/Y');
            return [
                'period_id' => $openPeriod->id,
                'period_key' => $openPeriod->period_key,
                'month_name' => $openPeriod->month_name,
                'start_date' => $openPeriod->start_date instanceof Carbon ? $openPeriod->start_date->toDateString() : (string) $openPeriod->start_date,
                'end_date' => $openPeriod->end_date instanceof Carbon ? $openPeriod->end_date->toDateString() : (string) $openPeriod->end_date,
                'days_remaining' => $diffInDays,
                'is_imminent' => $diffInDays <= 1,
                'message' => $diffInDays === 0
                    ? "Hari ini adalah hari terakhir periode persediaan {$openPeriod->month_name}. Segera lakukan tutup buku bulanan."
                    : "Periode persediaan {$openPeriod->month_name} akan berakhir dalam {$diffInDays} hari (tanggal {$formattedEndDate}). Segera selesaikan transaksi dan persiapan tutup buku.",
            ];
        }

        return null;
    }
}
