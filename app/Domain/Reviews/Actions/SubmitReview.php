<?php

namespace App\Domain\Reviews\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitReview
{
    /**
     * Leave a rating on a completed appointment and refresh the
     * business, service and employee rating aggregates.
     *
     * @throws InvalidArgumentException
     */
    public function execute(
        Booking $booking,
        User $client,
        int $rating,
        ?string $title = null,
        ?string $body = null,
        bool $wouldRecommend = true,
    ): Review {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('A rating must be between 1 and 5.');
        }

        if ($booking->client_user_id !== $client->id) {
            throw new InvalidArgumentException('You can only review your own appointments.');
        }

        if ($booking->status !== BookingStatus::Completed) {
            throw new InvalidArgumentException('Reviews can only be left after the service has been completed.');
        }

        if ($booking->review()->exists()) {
            throw new InvalidArgumentException('This appointment has already been reviewed.');
        }

        return DB::transaction(function () use ($booking, $client, $rating, $title, $body, $wouldRecommend): Review {
            $review = Review::create([
                'booking_id' => $booking->id,
                'service_id' => $booking->service_id,
                'business_id' => $booking->business_id,
                'client_user_id' => $client->id,
                'rating' => $rating,
                'title' => $title,
                'body' => $body,
                'would_recommend' => $wouldRecommend,
                'is_published' => true,
            ]);

            $booking->service->recalculateRatings();
            $booking->business->recalculateRatings();

            $booking->employee?->recalculateRatingAggregate();

            return $review;
        });
    }
}
