<?php

return [
    /*
    | Selfies are deleted once their month is over. Set a number of extra months
    | to keep them longer (e.g. 1 keeps last month's photos until the month after).
    */
    'selfie_retention_months' => (int) env('SELFIE_RETENTION_MONTHS', 0),
];
