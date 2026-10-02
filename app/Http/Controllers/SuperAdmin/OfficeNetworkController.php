<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateOfficeNetworkRequest;
use App\Models\AuditLog;
use App\Models\Office;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeNetworkController extends Controller
{
    public function edit(Request $request, Office $office): View
    {
        $payload = $office->wifiQrPayload();

        return view('superadmin.offices.network', [
            'office' => $office,
            'currentIp' => $request->ip(),
            'currentIpEntry' => Office::networkEntryFor((string) $request->ip()),
            'proxyChain' => [
                'x_forwarded_for' => $request->headers->get('X-Forwarded-For'),
                'x_real_ip' => $request->headers->get('X-Real-IP'),
                'connecting_ip' => $request->server->get('REMOTE_ADDR'),
            ],
            'currentIpAllowed' => $office->isNetworkConfigured() && $office->acceptsNetwork($request->ip()),
            'invalidEntries' => array_values(array_filter($office->allowedIpList(), fn (string $entry) => Office::isNonOfficeEntry($entry))),
            'staff' => $office->employees()->orderBy('full_name')->get(['id', 'full_name', 'employee_code']),
            'otherOffices' => Office::whereKeyNot($office->id)->orderBy('name')->get(),
            'wifiQrSvg' => $payload ? QrCodeService::svg($payload, 220) : null,
        ]);
    }

    public function update(UpdateOfficeNetworkRequest $request, Office $office): RedirectResponse
    {
        $data = $request->validated();

        // Keep the stored password when the field is left blank.
        if ($data['wifi_security'] === 'nopass' || $request->boolean('clear_password')) {
            $data['wifi_password'] = null;
        } elseif (blank($data['wifi_password'] ?? null)) {
            unset($data['wifi_password']);
        }
        unset($data['testing_mode'], $data['clear_password'], $data['apply_to_all']);

        $data['allowed_ips'] = implode("\n", $request->ipList());
        $data['network_check_enabled'] = ! $request->boolean('testing_mode');

        // One company WiFi often serves every branch record — save it everywhere in one go.
        $offices = $request->boolean('apply_to_all') ? Office::all() : collect([$office]);

        foreach ($offices as $target) {
            $old = $target->only(['wifi_ssid', 'wifi_security', 'allowed_ips', 'network_check_enabled']);
            $target->update($data);

            AuditLog::record(
                'update',
                'office_network',
                "Updated WiFi / NFC network settings for \"{$target->name}\"",
                $old,
                $target->only(['wifi_ssid', 'wifi_security', 'allowed_ips', 'network_check_enabled']),
            );
        }

        $message = $offices->count() > 1
            ? "Network settings saved for all {$offices->count()} offices."
            : 'Office network settings saved.';

        return redirect()->route('super-admin.offices.network.edit', $office)->with('success', $message);
    }
}
