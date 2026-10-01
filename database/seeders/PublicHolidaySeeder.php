<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use Illuminate\Database\Seeder;

class PublicHolidaySeeder extends Seeder
{
    /**
     * Fixed-date Malaysian national holidays. Lunar/religious holidays change every
     * year, so HR adds those from the Public Holidays page.
     */
    public function run(): void
    {
        foreach ([
            ['Labour Day', '2026-05-01'],
            ['National Day (Hari Kebangsaan)', '2026-08-31'],
            ['Malaysia Day', '2026-09-16'],
            ['Christmas Day', '2026-12-25'],
        ] as [$name, $date]) {
            PublicHoliday::firstOrCreate(['name' => $name], ['date' => $date, 'is_recurring' => true]);
        }
    }
}
