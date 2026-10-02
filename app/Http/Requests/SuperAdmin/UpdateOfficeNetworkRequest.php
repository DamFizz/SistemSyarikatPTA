<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Office;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeNetworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'wifi_ssid' => ['required', 'string', 'max:32'],
            'wifi_security' => ['required', Rule::in(array_keys(Office::WIFI_SECURITY_TYPES))],
            'wifi_password' => ['nullable', 'string', 'max:63', Rule::when($this->input('wifi_security') === 'WPA', ['min:8'])],
            'allowed_ips' => [
                Rule::requiredIf(! $this->boolean('testing_mode')),
                'nullable',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, Closure $fail) {
                    $proxies = $this->proxyHops();

                    foreach ($this->ipList() as $entry) {
                        if (! self::isValidIpOrCidr($entry)) {
                            $fail("\"{$entry}\" is not a valid IP address or CIDR range.");
                        } elseif (in_array(explode('/', $entry, 2)[0], $proxies, true) || Office::isNonOfficeEntry($entry)) {
                            $fail("\"{$entry}\" is a server / proxy address, not the office internet connection. Remove it and use “Add my current network” while connected to the office WiFi.");
                        }
                    }
                },
            ],
            'testing_mode' => ['nullable', 'boolean'],
            'clear_password' => ['nullable', 'boolean'],
            'apply_to_all' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'allowed_ips.required' => 'Add at least one office public IP, or turn on testing mode.',
        ];
    }

    /**
     * @return list<string>
     */
    public function ipList(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/[\s,;]+/', (string) $this->input('allowed_ips')) ?: []
        ))));
    }

    /**
     * Proxies this very request passed through (everything except the resolved client).
     *
     * @return list<string>
     */
    private function proxyHops(): array
    {
        $chain = array_map('trim', explode(',', (string) $this->headers->get('X-Forwarded-For')));
        $hops = [...$chain, (string) $this->server->get('REMOTE_ADDR')];

        return array_values(array_diff(array_filter($hops), [$this->ip()]));
    }

    private static function isValidIpOrCidr(string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return filter_var($entry, FILTER_VALIDATE_IP) !== false;
        }

        [$ip, $mask] = explode('/', $entry, 2);

        if (! ctype_digit($mask) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $max = str_contains($ip, ':') ? 128 : 32;

        return (int) $mask >= 8 && (int) $mask <= $max;
    }
}
