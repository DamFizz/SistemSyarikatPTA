<?php

use App\Models\Office;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Before the client-IP fix, "Add my current network" saved the hosting platform's edge
 * address (e.g. 152.233.x.x) instead of the office's. Those entries can never match a
 * staff device, so strip them; the remaining real office addresses are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('offices')->get(['id', 'allowed_ips']) as $office) {
            $entries = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $office->allowed_ips) ?: [])));
            $kept = array_values(array_filter($entries, fn (string $entry) => ! Office::isNonOfficeEntry($entry)));

            if ($kept !== $entries) {
                DB::table('offices')->where('id', $office->id)->update(['allowed_ips' => $kept ? implode("\n", $kept) : null]);
            }
        }
    }

    public function down(): void
    {
        // Removed entries were invalid; nothing to restore.
    }
};
