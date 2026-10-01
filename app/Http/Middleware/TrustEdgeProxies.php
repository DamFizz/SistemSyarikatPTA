<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Resolves the employee's real public IP for the office-WiFi check.
 *
 * On Railway every request passes through two hops: a public edge (whose IP changes
 * per request) and an internal proxy on a carrier-grade NAT address (100.64.0.0/10).
 * Railway strips any client-supplied X-Forwarded-For at the edge, so the left-most
 * entry is the real client — verified against the live deployment. When the request
 * arrives through that platform proxy we therefore trust the whole chain.
 *
 * Elsewhere (e.g. a plain WAMP server) only the connecting proxy and Fastly's
 * published ranges are trusted, so a client cannot spoof its address.
 */
class TrustEdgeProxies extends TrustProxies
{
    /** Address ranges a hosting platform's internal proxy connects from. */
    private const PLATFORM_PROXY_RANGES = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '100.64.0.0/10', 'fc00::/7'];

    protected function setTrustedProxyIpAddresses(Request $request)
    {
        $remote = (string) $request->server->get('REMOTE_ADDR');

        if (config('attendance.behind_platform_proxy') && $remote !== '' && IpUtils::checkIp($remote, self::PLATFORM_PROXY_RANGES)) {
            return $this->setTrustedProxyIpAddressesToSpecificIps($request, ['0.0.0.0/0', '::/0']);
        }

        // Only a reverse proxy on this same machine (loopback) is trusted directly — on a LAN
        // the connecting address is the employee's own device, which must not vouch for itself.
        $local = $remote !== '' && IpUtils::checkIp($remote, ['127.0.0.0/8', '::1']) ? [$remote] : [];

        return $this->setTrustedProxyIpAddressesToSpecificIps($request, [
            ...$local,
            ...config('attendance.edge_proxy_ranges', []),
        ]);
    }
}
