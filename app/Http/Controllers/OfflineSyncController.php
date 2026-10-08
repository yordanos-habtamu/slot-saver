<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\MarkNoShow;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Policies\BookingPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function __construct(
        protected MarkNoShow $markNoShow
    ) {}

    /**
     * Replay actions queued in IndexedDB while staff were offline.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actions' => 'required|array',
            'actions.*.id' => 'required|string',
            'actions.*.type' => 'required|string|in:check_in,mark_no_show',
            'actions.*.booking_id' => 'required|exists:bookings,id',
            'actions.*.timestamp' => 'required|numeric',
        ]);

        $results = [];

        foreach ($validated['actions'] as $item) {
            $booking = Booking::query()->whereKey($item['booking_id'])->first();
            if (! $booking) {
                $results[] = ['id' => $item['id'], 'status' => 'skipped', 'message' => 'Booking not found'];

                continue;
            }

            abort_unless((new BookingPolicy)->manageStatus($request->user(), $booking), 403);

            if ($item['type'] === 'check_in') {
                $booking->update([
                    'status' => BookingStatus::Completed,
                    'completed_at' => now(),
                ]);
                $results[] = ['id' => $item['id'], 'status' => 'synced', 'action' => 'check_in'];
            } elseif ($item['type'] === 'mark_no_show') {
                $this->markNoShow->execute($booking);
                $results[] = ['id' => $item['id'], 'status' => 'synced', 'action' => 'mark_no_show'];
            }
        }

        return response()->json([
            'status' => 'success',
            'synced_count' => count($results),
            'results' => $results,
        ]);
    }
}
