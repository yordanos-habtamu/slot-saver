<?php

namespace App\Domain\Risk;

use App\Models\Service;
use App\Models\User;

class DepositPolicy
{
    /**
     * Determine whether an appointment requires an upfront deposit and calculate the amount.
     *
     * @param  array{risk_score: float, risk_tier: string, requires_deposit: bool, suggested_deposit_amount: float}  $riskAssessment
     * @return array{deposit_required: bool, amount: float, reason: string}
     */
    public function evaluate(Service $service, ?User $client, array $riskAssessment): array
    {
        $mandatoryFee = (float) $service->booking_fee;

        // VIP Loyalty Waiver: Client has >= 5 completed appointments with 0 no-shows
        if ($client) {
            $noShows = $client->bookings()->where('status', 'no_show')->count();
            $completed = $client->bookings()->where('status', 'completed')->count();

            if ($completed >= 5 && $noShows === 0) {
                return [
                    'deposit_required' => $mandatoryFee > 0,
                    'amount' => $mandatoryFee,
                    'reason' => 'VIP loyalty waiver: Risk deposit waived due to 5+ attended visits with zero no-shows.',
                ];
            }
        }

        // High Risk Assessment
        if ($riskAssessment['requires_deposit'] || $riskAssessment['risk_tier'] === 'high') {
            $amount = max($mandatoryFee, $riskAssessment['suggested_deposit_amount']);

            return [
                'deposit_required' => true,
                'amount' => round($amount, 2),
                'reason' => 'High-demand slot / attendance risk threshold exceeded. Refundable deposit required to confirm slot.',
            ];
        }

        // Default: only standard service booking fee if configured
        return [
            'deposit_required' => $mandatoryFee > 0,
            'amount' => $mandatoryFee,
            'reason' => $mandatoryFee > 0 ? 'Standard service reservation fee.' : 'No deposit required.',
        ];
    }
}
