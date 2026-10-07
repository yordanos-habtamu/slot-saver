<?php

namespace App\Domain\Waitlist\Actions;

use App\Models\WaitlistEntry;
use Carbon\CarbonInterface;

class JoinWaitlist
{
    /**
     * Register a customer onto the service waitlist.
     */
    public function execute(
        int $businessId,
        int $clientUserId,
        int $serviceId,
        CarbonInterface $preferredDate,
        ?string $timeFrom = null,
        ?string $timeTo = null,
        ?int $preferredEmployeeId = null,
    ): WaitlistEntry {
        return WaitlistEntry::create([
            'business_id' => $businessId,
            'client_user_id' => $clientUserId,
            'service_id' => $serviceId,
            'preferred_employee_id' => $preferredEmployeeId,
            'preferred_date' => $preferredDate->toDateString(),
            'preferred_time_from' => $timeFrom,
            'preferred_time_to' => $timeTo,
            'status' => 'waiting',
        ]);
    }
}
