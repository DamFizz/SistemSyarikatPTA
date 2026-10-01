<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * Resolves the employee's real public IP behind Railway.
 *
 * Railway sometimes routes through Fastly, giving `X-Forwarded-For: <client>, <fastly-edge>`.
 * Trusting only the connecting proxy made Laravel pick the Fastly edge IP (which changes
 * per request), so the office-WiFi check failed at random. We trust the connecting proxy
 * plus Fastly's published ranges: Laravel walks the chain from the right, skips those, and
 * lands on the client. A spoofed entry added by the client sits further left and is ignored.
 */
class TrustEdgeProxies extends TrustProxies
{
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        $trusted = array_values(array_filter([
            $request->server->get('REMOTE_ADDR'),
            ...config('attendance.edge_proxy_ranges', []),
        ]));

        return $this->setTrustedProxyIpAddressesToSpecificIps($request, $trusted);
    }
}
