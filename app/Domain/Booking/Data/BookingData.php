<?php

namespace App\Domain\Booking\Data;

use Carbon\CarbonInterface;

readonly class BookingData
{
    public function __construct(
        public int $businessId,
        public int $locationId,
        public int $serviceId,
        public int $clientUserId,
        public ?int $employeeUserId,
        public CarbonInterface $startAt,
        public ?string $clientNote = null,
        public int $partySize = 1,
        public ?float $depositAmount = 0.0,
        public ?string $depositStatus = 'none',
        public ?float $riskScore = null,
        public ?string $riskTier = null,
    ) {}
}
