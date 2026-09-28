<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'latitude', 'longitude', 'allowed_radius_meters', 'nfc_tag_id'])]
class Office extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(AttendanceQrToken::class);
    }

    /**
     * Distance in meters from this office to given coordinates (Haversine formula).
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad((float) $this->latitude);
        $lngFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2
        ));

        return $angle * $earthRadius;
    }
}
