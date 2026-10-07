<?php

namespace App\Actions\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Review;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Support\Carbon;

class ComputeBusinessAnalytics
{
    /**
     * Platform analytics for a single business: lifetime totals, a weekly
     * bookings series, top services and recent booking/review activity.
     *
     * @return array<string, mixed>
     */
    public function handle(Business $business, int $weeks = 26): array
    {
        $since = Carbon::now()->startOfWeek()->subWeeks($weeks - 1);

        $statusCounts = Booking::query()
            ->where('business_id', $business->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        $totals = [
            'bookings' => (int) $statusCounts->sum(),
            'completed' => (int) $statusCounts->get('completed', 0),
            'cancelled' => (int) $statusCounts->get('cancelled', 0),
            'no_show' => (int) $statusCounts->get('no_show', 0),
            'confirmed' => (int) $statusCounts->get('confirmed', 0),
            'pending' => (int) $statusCounts->get('pending', 0),
            'revenue' => $this->completedRevenue($business),
            'cancellation_fees' => round((float) Booking::query()
                ->where('business_id', $business->id)
                ->whereNotNull('cancellation_fee')
                ->sum('cancellation_fee'), 2),
            'waitlist_refills' => WaitlistEntry::query()
                ->where('business_id', $business->id)
                ->where('status', 'claimed')
                ->count(),
        ];

        $totals['no_show_rate'] = $totals['bookings'] > 0
            ? round(($totals['no_show'] / $totals['bookings']) * 100, 1)
            : 0.0;

        $totals['average_ticket'] = $totals['completed'] > 0
            ? round($totals['revenue'] / $totals['completed'], 2)
            : 0.0;

        return [
            'period_weeks' => $weeks,
            'totals' => $totals,
            'weekly' => $this->weeklySeries($business, $since, $weeks),
            'top_services' => $this->topServices($business),
            'recent_bookings' => $this->recentBookings($business),
            'recent_reviews' => $this->recentReviews($business),
            'rating' => [
                'average' => $business->rating_average !== null ? (float) $business->rating_average : null,
                'count' => (int) $business->rating_count,
            ],
        ];
    }

    /**
     * Lifetime revenue from completed appointments.
     */
    protected function completedRevenue(Business $business): float
    {
        return round((float) Booking::query()
            ->where('business_id', $business->id)
            ->where('status', BookingStatus::Completed->value)
            ->sum('total_amount'), 2);
    }

    /**
     * Bookings per ISO week, bucketed in PHP so the query stays portable.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function weeklySeries(Business $business, Carbon $since, int $weeks): array
    {
        $buckets = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $weekStart = $since->copy()->addWeeks($offset);

            $buckets[$weekStart->toDateString()] = [
                'week_start' => $weekStart->toDateString(),
                'total' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'no_show' => 0,
            ];
        }

        Booking::query()
            ->where('business_id', $business->id)
            ->where('start_at', '>=', $since)
            ->get(['start_at', 'status'])
            ->each(function (Booking $booking) use (&$buckets): void {
                $key = $booking->start_at->copy()->startOfWeek()->toDateString();

                if (! isset($buckets[$key])) {
                    return;
                }

                $buckets[$key]['total']++;
                $status = $booking->status->value;

                if (isset($buckets[$key][$status])) {
                    $buckets[$key][$status]++;
                }
            });

        return array_values($buckets);
    }

    /**
     * Busiest services with completed revenue.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function topServices(Business $business): array
    {
        return Booking::query()
            ->where('business_id', $business->id)
            ->selectRaw(
                'service_id, COUNT(*) as bookings_count, COALESCE(SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END), 0) as revenue',
                [BookingStatus::Completed->value]
            )
            ->groupBy('service_id')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->toBase()
            ->get()
            ->map(fn ($row): array => [
                'service_id' => $row->service_id,
                'name' => Service::query()->whereKey($row->service_id)->value('name') ?? 'Unknown service',
                'bookings_count' => (int) $row->bookings_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentBookings(Business $business): array
    {
        return Booking::query()
            ->where('business_id', $business->id)
            ->with(['client:id,name', 'service:id,name', 'location:id,name'])
            ->latest('start_at')
            ->limit(10)
            ->get()
            ->map(fn (Booking $booking): array => [
                'reference_code' => $booking->reference_code,
                'client_name' => $booking->client->name,
                'service_name' => $booking->service->name,
                'location_name' => $booking->location->name,
                'start_at' => $booking->start_at->toIso8601String(),
                'status' => $booking->status->value,
                'total_amount' => (float) $booking->total_amount,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentReviews(Business $business): array
    {
        return Review::query()
            ->where('business_id', $business->id)
            ->where('is_published', true)
            ->with('client:id,name')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Review $review): array => [
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'client_name' => $review->client->name,
                'created_at' => $review->created_at->toDateString(),
                'would_recommend' => (bool) $review->would_recommend,
            ])
            ->all();
    }
}
