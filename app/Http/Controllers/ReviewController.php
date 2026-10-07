<?php

namespace App\Http\Controllers;

use App\Domain\Reviews\Actions\SubmitReview;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(
        protected SubmitReview $submitReview
    ) {}

    /**
     * Leave a rating on the signed-in client's own completed appointment.
     */
    public function store(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('review', $booking);

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:120',
            'body' => 'nullable|string|max:2000',
            'would_recommend' => 'nullable|boolean',
        ]);

        $review = $this->submitReview->execute(
            $booking,
            $request->user(),
            (int) $validated['rating'],
            $validated['title'] ?? null,
            $validated['body'] ?? null,
            $validated['would_recommend'] ?? true,
        );

        return response()->json([
            'status' => 'success',
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'is_published' => $review->is_published,
            ],
        ], 201);
    }
}
