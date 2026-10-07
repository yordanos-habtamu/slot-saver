<?php

namespace App\Actions\Owner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ComputeOwnerDashboard
{
    /**
     * Real KPI/funnel/risk metrics for the owner dashboard.
     *
     * Metric definitions mirror analytics/metabase-dashboards/export.json:
     * recovered revenue is claimed waitlist entries valued at current service
     * prices, the funnel groups reminders by delivery stage, and the risk
     * distribution buckets bookings by tier (deriving from risk_score when the
     * tier column was never populated).
     *
     * @return array{kpis: array<string, float|int>, funnel: array<string, int>, risk_distribution: array<string, int>}
     */
    public function handle(?Business $business): array
    {
        if ($business === null) {
            return $this->emptyMetrics();
        }

        $businessId = $business->id;

        // Era split over the 26-week window: the baseline era is weeks 26–23
        // ago (matching AnalyticsMetricsTest), the current era is the last 8 weeks.
        $windowFrom = Carbon::now()->subWeeks(26)->startOfWeek();
        $baselineTo = Carbon::now()->subWeeks(22)->endOfWeek();
        $currentFrom = Carbon::now()->subWeeks(8);

        $baseline = $this->noShowCounts($businessId, function (Builder $query) use ($windowFrom, $baselineTo): void {
            $query->where('start_at', '>=', $windowFrom)
                ->where('start_at', '<=', $baselineTo);
        });
        $current = $this->noShowCounts($businessId, function (Builder $query) use ($currentFrom): void {
            $query->where('start_at', '>=', $currentFrom);
        });
        $window = $this->noShowCounts($businessId, function (Builder $query) use ($windowFrom): void {
            $query->where('start_at', '>=', $windowFrom);
        });

        $baselineRate = $this->rate($baseline, $window);
        $currentRate = $this->rate($current, $window);

        $refilled = WaitlistEntry::query()
            ->where('business_id', $businessId)
            ->where('status', 'claimed')
            ->count();

        $recoveredRevenue = round((float) Service::query()
            ->whereIn('id', WaitlistEntry::query()
                ->where('business_id', $businessId)
                ->where('status', 'claimed')
                ->pluck('service_id'))
            ->sum('price'), 2);

        $funnel = $this->funnel($businessId);
        $riskDistribution = $this->riskDistribution($businessId);

        return [
            'kpis' => [
                'no_show_rate' => $currentRate,
                'baseline_no_show_rate' => $baselineRate,
                // Positive means the current era improved on the baseline era.
                'reduction_percentage' => $baselineRate > 0
                    ? round((($baselineRate - $currentRate) / $baselineRate) * 100, 1)
                    : 0.0,
                'recovered_revenue' => $recoveredRevenue,
                'refilled_slots_count' => $refilled,
                // Each automated reminder replaces a ~3 minute phone call.
                'admin_hours_saved' => round($funnel['sent'] * 3 / 60, 1),
            ],
            'funnel' => $funnel,
            'risk_distribution' => $riskDistribution,
        ];
    }

    /**
     * Bookings and no-shows for a window around one business.
     *
     * @param  \Closure(Builder<Booking>): void  $constrain
     * @return array{total: int, no_shows: int}
     */
    protected function noShowCounts(int $businessId, $constrain): array
    {
        $query = Booking::query()->where('business_id', $businessId);
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
     * Cumulative reminder funnel: terminal delivery states imply every earlier
     * stage, and interactive replies drive reschedule/cancel counts.
     *
     * @return array<string, int>
     */
    protected function funnel(int $businessId): array
    {
        $reminders = fn () => Reminder::query()->whereIn(
            'booking_id',
            Booking::query()->where('business_id', $businessId)->select('id')
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
            ->where('business_id', $businessId)
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
     * Booking counts per risk tier, deriving the tier from risk_score when the
     * column is unset. Thresholds match the UI labels: low < 0.35,
     * medium 0.35–0.65, high ≥ 0.65 (the deposit threshold).
     *
     * @return array<string, int>
     */
    protected function riskDistribution(int $businessId): array
    {
        $rows = Booking::query()
            ->where('business_id', $businessId)
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
     * @return array{kpis: array<string, float|int>, funnel: array<string, int>, risk_distribution: array<string, int>}
     */
    protected function emptyMetrics(): array
    {
        return [
            'kpis' => [
                'no_show_rate' => 0.0,
                'baseline_no_show_rate' => 0.0,
                'reduction_percentage' => 0.0,
                'recovered_revenue' => 0.0,
                'refilled_slots_count' => 0,
                'admin_hours_saved' => 0.0,
            ],
            'funnel' => [
                'sent' => 0,
                'delivered' => 0,
                'read' => 0,
                'confirmed' => 0,
                'rescheduled' => 0,
                'cancelled' => 0,
                'no_show' => 0,
            ],
            'risk_distribution' => ['low' => 0, 'medium' => 0, 'high' => 0],
        ];
    }
}
