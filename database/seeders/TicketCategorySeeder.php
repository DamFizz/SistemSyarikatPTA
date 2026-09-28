<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Computer', 'Printer', 'Internet', 'Software', 'Hardware',
            'Air Conditioning', 'Office Equipment', 'Others',
        ];

        foreach ($categories as $name) {
            TicketCategory::firstOrCreate(['name' => $name]);
        }
    }
}
