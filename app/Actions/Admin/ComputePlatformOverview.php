<?php

namespace App\Actions\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ComputePlatformOverview
{
    /**
     * Platform-wide metrics the super admin sees instead of any single
     * business: the same no-show, recovery, funnel and risk counters as the
     * owner dashboard, aggregated across every registered business.
     *
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $windowFrom = Carbon::now()->subWeeks(26)->startOfWeek();
        $baselineTo = Carbon::now()->subWeeks(22)->endOfWeek();
        $currentFrom = Carbon::now()->subWeeks(8);

        $baseline = $this->noShowCounts(function (Builder $query) use ($windowFrom, $baselineTo): void {
            $query->where('start_at', '>=', $windowFrom)
                ->where('start_at', '<=', $baselineTo);
        });
        $current = $this->noShowCounts(function (Builder $query) use ($currentFrom): void {
            $query->where('start_at', '>=', $currentFrom);
        });
        $window = $this->noShowCounts(function (Builder $query) use ($windowFrom): void {
            $query->where('start_at', '>=', $windowFrom);
        });

        $baselineRate = $this->rate($baseline, $window);
        $currentRate = $this->rate($current, $window);

        $refilled = WaitlistEntry::query()
            ->where('status', 'claimed')
            ->count();

        $recoveredRevenue = round((float) Service::query()
            ->whereIn('id', WaitlistEntry::query()
                ->where('status', 'claimed')
                ->pluck('service_id'))
            ->sum('price'), 2);

        $funnel = $this->funnel();
        $riskDistribution = $this->riskDistribution();

        return [
            'kpis' => [
                'no_show_rate' => $currentRate,
                'baseline_no_show_rate' => $baselineRate,
                'reduction_percentage' => $baselineRate > 0
                    ? round((($baselineRate - $currentRate) / $baselineRate) * 100, 1)
                    : 0.0,
                'recovered_revenue' => $recoveredRevenue,
                'refilled_slots_count' => $refilled,
                'admin_hours_saved' => round($funnel['sent'] * 3 / 60, 1),
            ],
            'funnel' => $funnel,
            'risk_distribution' => $riskDistribution,
            'summary' => [
                'business_count' => (int) Business::query()->count(),
                'bookings_count' => $window['total'],
                'waitlist_refills' => $refilled,
            ],
            'top_businesses' => $this->topBusinesses($windowFrom),
            'recent_bookings' => $this->recentBookings(),
        ];
    }

    /**
     * Bookings and no-shows for a window across the whole platform.
     *
     * @param  \Closure(Builder<Booking>): void  $constrain
     * @return array{total: int, no_shows: int}
     */
    protected function noShowCounts($constrain): array
    {
        $query = Booking::query();
        $constrain($query);

        $row = $query
            ->selectRaw(
                'COUNT(*) as total, COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as no_shows',
                [BookingStatus::NoShow->value]
            )
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'no_shows' => (int) ($row->no_shows ?? 0),
        ];
    }

    /**
     * No-show rate for a window, falling back to the 26-week window rate so a
     * sparse dataset never reports a misleading percentage.
     *
     * @param  array{total: int, no_shows: int}  $counts
     * @param  array{total: int, no_shows: int}  $fallback
     */
    protected function rate(array $counts, array $fallback): float
    {
        if ($counts['total'] === 0) {
            $counts = $fallback;
        }

        return $counts['total'] > 0
            ? round(($counts['no_shows'] / $counts['total']) * 100, 1)
            : 0.0;
    }

    /**
     * Cumulative reminder funnel across every business.
     *
     * @return array<string, int>
     */
    protected function funnel(): array
    {
        $reminders = fn () => Reminder::query()->whereIn(
            'booking_id',
            Booking::query()->select('id')
        );

        $sent = $reminders()->whereNotNull('sent_at')->count();
        $delivered = $reminders()
            ->whereIn('delivery_status', ['delivered', 'read', 'confirmed'])
            ->count();
        $read = $reminders()
            ->whereIn('delivery_status', ['read', 'confirmed'])
            ->count();
        $confirmed = $reminders()->where('delivery_status', 'confirmed')->count();
        $rescheduled = $reminders()->where('interactive_action', 'reschedule')->count();
        $cancelled = $reminders()->where('interactive_action', 'cancel')->count();

        $noShow = Booking::query()
            ->where('status', BookingStatus::NoShow->value)
            ->whereHas('reminders', fn ($query) => $query->whereNotNull('sent_at'))
            ->count();

        return [
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'confirmed' => $confirmed,
            'rescheduled' => $rescheduled,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
        ];
    }

    /**
     * Booking counts per risk tier across the platform.
     *
     * @return array<string, int>
     */
    protected function riskDistribution(): array
    {
        $rows = Booking::query()
            ->selectRaw(
                "COALESCE(risk_tier, CASE
                    WHEN risk_score IS NULL THEN 'low'
                    WHEN risk_score >= 0.65 THEN 'high'
                    WHEN risk_score >= 0.35 THEN 'medium'
                    ELSE 'low'
                END) as tier, COUNT(*) as aggregate"
            )
            ->groupBy('tier')
            ->toBase()
            ->get();

        $distribution = array_fill_keys(['low', 'medium', 'high'], 0);

        foreach ($rows as $row) {
            $tier = $row->tier;

            if (is_string($tier) && isset($distribution[$tier])) {
                $distribution[$tier] = (int) $row->aggregate;
            }
        }

        return $distribution;
    }

    /**
     * The busiest businesses over the 26-week window, with their no-show rate.
     *
     * @return array<int, array<string, int|float|string>>
     */
    protected function topBusinesses(Carbon $windowFrom): array
    {
        $rows = Booking::query()
            ->where('start_at', '>=', $windowFrom)
            ->selectRaw(
                'business_id, COUNT(*) as bookings_count, COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as no_shows',
                [BookingStatus::NoShow->value]
            )
            ->groupBy('business_id')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->toBase()
            ->get();

        return $rows
            ->map(fn ($row): array => [
                'id' => (int) $row->business_id,
                'name' => Business::query()->whereKey($row->business_id)->value('name') ?? 'Unknown business',
                'bookings_count' => (int) $row->bookings_count,
                'no_show_rate' => $row->bookings_count > 0
                    ? round(($row->no_shows / $row->bookings_count) * 100, 1)
                    : 0.0,
            ])
            ->all();
    }

    /**
     * The latest platform-wide appointments.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function recentBookings(): array
    {
        return Booking::query()
            ->with(['client:id,name', 'service:id,name', 'location:id,name', 'business:id,name'])
            ->latest('start_at')
            ->limit(12)
            ->get()
            ->map(fn (Booking $booking): array => [
                'reference_code' => $booking->reference_code,
                'business_name' => $booking->business->name,
                'client_name' => $booking->client->name,
                'service_name' => $booking->service->name,
                'location_name' => $booking->location->name,
                'start_at' => $booking->start_at->toIso8601String(),
                'status' => $booking->status->value,
                'total_amount' => (float) $booking->total_amount,
            ])
            ->all();
    }
}
