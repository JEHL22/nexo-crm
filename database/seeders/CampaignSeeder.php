<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['WOW', 'CLARO'] as $name) {
            Campaign::firstOrCreate(['name' => $name], ['active' => true]);
        }
    }
}