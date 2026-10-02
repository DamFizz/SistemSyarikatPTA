<?php

namespace App\Support;

/**
 * Everything captured on the employee's device for a single clock-in or clock-out.
 */
final readonly class AttendanceCapture
{
    public function __construct(
        public ?float $latitude,
        public ?float $longitude,
        public ?float $accuracy,
        public string $selfieDataUrl,
        public ?string $ip,
        public ?string $userAgent = null,
        public ?string $deviceHash = null,
        public ?string $restDayReason = null,
    ) {}
}
