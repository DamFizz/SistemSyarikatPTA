<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpFoundation\IpUtils;

#[Fillable([
    'name', 'address', 'latitude', 'longitude', 'allowed_radius_meters',
    'wifi_ssid', 'wifi_password', 'wifi_security', 'allowed_ips', 'network_check_enabled',
])]
#[Hidden(['wifi_password'])]
class Office extends Model
{
    use HasFactory;

    public const WIFI_SECURITY_TYPES = ['WPA' => 'WPA / WPA2 / WPA3', 'WEP' => 'WEP (legacy)', 'nopass' => 'Open (no password)'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'wifi_password' => 'encrypted',
            'network_check_enabled' => 'boolean',
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

    /**
     * Public IPs / CIDR ranges of the office internet connection.
     *
     * @return list<string>
     */
    public function allowedIpList(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,;]+/', (string) $this->allowed_ips) ?: []
        )));
    }

    public function isNetworkConfigured(): bool
    {
        return $this->allowedIpList() !== [];
    }

    /**
     * Whether a request from the given IP counts as being on the office WiFi.
     */
    public function acceptsNetwork(?string $ip): bool
    {
        if (! $this->network_check_enabled) {
            return true;
        }

        return $ip !== null && $this->isNetworkConfigured() && IpUtils::checkIp($ip, $this->allowedIpList());
    }

    /**
     * Standard "WIFI:" payload understood by Android/iOS cameras and NFC writer apps.
     */
    public function wifiQrPayload(): ?string
    {
        if (! $this->wifi_ssid) {
            return null;
        }

        $escape = fn (string $value) => preg_replace('/([\\\\;,:"])/', '\\\\$1', $value);

        $payload = 'WIFI:T:'.$this->wifi_security.';S:'.$escape($this->wifi_ssid).';';

        if ($this->wifi_security !== 'nopass' && $this->wifi_password) {
            $payload .= 'P:'.$escape($this->wifi_password).';';
        }

        return $payload.';';
    }
}
