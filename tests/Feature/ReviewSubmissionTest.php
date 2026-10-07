<?php

namespace Tests\Feature;

use App\Domain\Reviews\Actions\SubmitReview;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_a_review_updates_business_service_and_employee_aggregates(): void
    {
        $employee = User::factory()->employee()->create();

        $booking = Booking::factory()->completed()->create([
            'employee_user_id' => $employee->id,
        ]);

        $client = User::query()->findOrFail($booking->client_user_id);
        $service = $booking->service()->first();
        $business = Business::query()->findOrFail($booking->business_id);

        $review = (new SubmitReview)->execute(
            $booking,
            $client,
            5,
            'Outstanding',
            'Best in the city.',
            true
        );

        $this->assertInstanceOf(Review::class, $review);
        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'is_published' => true,
        ]);

        $business->refresh();
        $service->refresh();
        $employee->refresh();

        $this->assertSame(1, $business->rating_count);
        $this->assertEquals(5.0, (float) $business->rating_average);

        $this->assertSame(1, $service->rating_count);
        $this->assertEquals(5.0, (float) $service->rating_average);
        $this->assertEquals(100.0, (float) $service->recommendation_percentage);

        $this->assertSame(1, $employee->rating_count);
        $this->assertEquals(5.0, (float) $employee->rating_average);
    }

    public function test_reviews_are_rejected_before_the_appointment_is_completed(): void
    {
        $booking = Booking::factory()->pending()->create();
        $client = User::query()->findOrFail($booking->client_user_id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('after the service has been completed');

        (new SubmitReview)->execute($booking, $client, 5);
    }

    public function test_clients_cannot_review_someone_elses_appointment(): void
    {
        $booking = Booking::factory()->completed()->create();
        $otherUser = User::factory()->client()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('your own appointments');

        (new SubmitReview)->execute($booking, $otherUser, 5);
    }

    public function test_an_appointment_can_only_be_reviewed_once(): void
    {
        $booking = Booking::factory()->completed()->create();
        $client = User::query()->findOrFail($booking->client_user_id);

        (new SubmitReview)->execute($booking, $client, 4);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been reviewed');

        (new SubmitReview)->execute($booking, $client, 5);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $booking = Booking::factory()->completed()->create();
        $client = User::query()->findOrFail($booking->client_user_id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('between 1 and 5');

        (new SubmitReview)->execute($booking, $client, 6);
    }

    public function test_unpublished_reviews_do_not_count_towards_aggregates(): void
    {
        $booking = Booking::factory()->completed()->create();
        $client = User::query()->findOrFail($booking->client_user_id);
        $business = Business::query()->findOrFail($booking->business_id);

        (new SubmitReview)->execute($booking, $client, 1, 'Terrible', 'Never again.', false);

        $this->assertSame(1, $business->fresh()->rating_count);

        Review::query()->where('booking_id', $booking->id)->update(['is_published' => false]);
        $business->recalculateRatings();

        $business->refresh();
        $this->assertSame(0, $business->rating_count);
        $this->assertNull($business->rating_average);
    }
}
