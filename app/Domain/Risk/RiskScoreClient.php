<?php

namespace App\Domain\Risk;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RiskScoreClient
{
    /**
     * Call the Python risk microservice to predict no-show probability.
     * Includes 500ms timeout and graceful fallback heuristics if offline.
     *
     * @param  array<string, float|int>  $features
     * @return array{
     *     risk_score: float,
     *     risk_tier: string,
     *     requires_deposit: bool,
     *     suggested_deposit_amount: float,
     *     top_risk_factors: array<int, array{factor: string, impact: string, description: string}>,
     *     fallback_used: bool
     * }
     */
    public function calculateRiskScore(array $features): array
    {
        $serviceUrl = config('services.risk_service.url', 'http://127.0.0.1:8001');
        $timeout = config('services.risk_service.timeout', 0.5);

        try {
            $response = Http::timeout($timeout)->post("{$serviceUrl}/score", $features);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'risk_score' => (float) $data['risk_score'],
                    'risk_tier' => (string) $data['risk_tier'],
                    'requires_deposit' => (bool) $data['requires_deposit'],
                    'suggested_deposit_amount' => (float) $data['suggested_deposit_amount'],
                    'top_risk_factors' => $data['top_risk_factors'] ?? [],
                    'fallback_used' => false,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Risk microservice unreachable: {$e->getMessage()}. Using local heuristics fallback.");
        }

        return $this->localHeuristicsFallback(
            leadTimeHours: (float) ($features['lead_time_hours'] ?? 24.0),
            priorNoShows: (int) ($features['prior_no_shows'] ?? 0),
            priorCompleted: (int) ($features['prior_completed'] ?? 0),
            servicePrice: (float) ($features['service_price'] ?? 50.0),
            depositPaid: (bool) ($features['deposit_paid'] ?? false),
        );
    }

    /**
     * Call the Python risk microservice to predict no-show probability.
     * Includes 500ms timeout and graceful fallback heuristics if offline.
     *
     * @return array{
     *     risk_score: float,
     *     risk_tier: string,
     *     requires_deposit: bool,
     *     suggested_deposit_amount: float,
     *     top_risk_factors: array<int, array{factor: string, impact: string, description: string}>,
     *     fallback_used: bool
     * }
     */
    public function scoreBooking(
        int $clientUserId,
        CarbonInterface $startAt,
        int $durationMinutes,
        float $servicePrice,
        bool $depositPaid = false
    ): array {
        $client = User::find($clientUserId);
        $leadTimeHours = max(0.5, round(now()->diffInMinutes($startAt, false) / 60, 2));

        $priorNoShows = $client
            ? $client->bookings()->where('status', 'no_show')->count()
            : 0;

        $priorCompleted = $client
            ? $client->bookings()->where('status', 'completed')->count()
            : 0;

        $payload = [
            'lead_time_hours' => $leadTimeHours,
            'prior_no_shows' => $priorNoShows,
            'prior_completed' => $priorCompleted,
            'day_of_week' => $startAt->dayOfWeekIso - 1, // 0=Mon, 6=Sun
            'hour_of_day' => (int) $startAt->format('G'),
            'service_duration_min' => $durationMinutes,
            'service_price' => $servicePrice,
            'deposit_paid' => $depositPaid,
        ];

        $serviceUrl = config('services.risk_service.url', 'http://127.0.0.1:8001');
        $timeout = config('services.risk_service.timeout', 0.5);

        try {
            $response = Http::timeout($timeout)->post("{$serviceUrl}/score", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'risk_score' => (float) $data['risk_score'],
                    'risk_tier' => (string) $data['risk_tier'],
                    'requires_deposit' => (bool) $data['requires_deposit'],
                    'suggested_deposit_amount' => (float) $data['suggested_deposit_amount'],
                    'top_risk_factors' => $data['top_risk_factors'] ?? [],
                    'fallback_used' => false,
                ];
            }

            Log::warning("Risk microservice returned status {$response->status()}. Using local heuristics.");
        } catch (\Throwable $e) {
            Log::warning("Risk microservice unreachable: {$e->getMessage()}. Using local heuristics fallback.");
        }

        // Local Heuristics Fallback (Graceful Degradation)
        return $this->localHeuristicsFallback(
            leadTimeHours: $leadTimeHours,
            priorNoShows: $priorNoShows,
            priorCompleted: $priorCompleted,
            servicePrice: $servicePrice,
            depositPaid: $depositPaid,
        );
    }

    /**
     * Local rule-based fallback when ML microservice is unreachable.
     *
     * @return array{
     *     risk_score: float,
     *     risk_tier: string,
     *     requires_deposit: bool,
     *     suggested_deposit_amount: float,
     *     top_risk_factors: array<int, array{factor: string, impact: string, description: string}>,
     *     fallback_used: bool
     * }
     */
    protected function localHeuristicsFallback(
        float $leadTimeHours,
        int $priorNoShows,
        int $priorCompleted,
        float $servicePrice,
        bool $depositPaid
    ): array {
        $factors = [];
        $score = 0.20; // baseline

        if ($priorNoShows > 0) {
            $score += $priorNoShows * 0.35;
            $factors[] = [
                'factor' => 'prior_no_shows',
                'impact' => '+0.35',
                'description' => "Customer has missed {$priorNoShows} appointment(s) in the past.",
            ];
        }

        if ($leadTimeHours > 72) {
            $score += 0.25;
            $factors[] = [
                'factor' => 'lead_time',
                'impact' => '+0.25',
                'description' => 'Appointment booked more than 3 days in advance.',
            ];
        }

        if ($priorCompleted >= 3) {
            $score -= 0.15;
        }

        if ($depositPaid) {
            $score -= 0.50;
        }

        $score = max(0.05, min(0.99, round($score, 4)));
        $isHigh = $score >= 0.60;
        $isMed = $score >= 0.35;

        return [
            'risk_score' => $score,
            'risk_tier' => $isHigh ? 'high' : ($isMed ? 'medium' : 'low'),
            'requires_deposit' => $isHigh,
            'suggested_deposit_amount' => $isHigh ? max(10.0, round($servicePrice * 0.25, 2)) : 0.0,
            'top_risk_factors' => $factors,
            'fallback_used' => true,
        ];
    }
}
