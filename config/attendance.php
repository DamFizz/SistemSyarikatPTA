<?php

return [
    /*
    | Selfies are deleted once their month is over. Set a number of extra months
    | to keep them longer (e.g. 1 keeps last month's photos until the month after).
    */
    'selfie_retention_months' => (int) env('SELFIE_RETENTION_MONTHS', 0),

    /*
    | CDN / edge proxies that may sit between the employee and the app (in addition
    | to the proxy that connects directly). Fastly's published ranges:
    | https://api.fastly.com/public-ip-list — Railway routes some traffic through Fastly.
    */
    'edge_proxy_ranges' => [
        '23.235.32.0/20', '43.249.72.0/22', '103.244.50.0/24', '103.245.222.0/23', '103.245.224.0/24',
        '104.156.80.0/20', '140.248.64.0/18', '140.248.128.0/17', '146.75.0.0/17', '151.101.0.0/16',
        '157.52.64.0/18', '167.82.0.0/17', '167.82.128.0/20', '167.82.160.0/20', '167.82.224.0/20',
        '172.111.64.0/18', '185.31.16.0/22', '199.27.72.0/21', '199.232.0.0/16',
        '2a04:4e40::/32', '2a04:4e42::/32',
    ],
];
