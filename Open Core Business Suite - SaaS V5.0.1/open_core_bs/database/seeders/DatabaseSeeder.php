<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('cache:clear');

        // In local/demo/testing environments, run DemoSeeder (includes all essential data + demo data)
        // In production, run LiveSeeder (only essential data)
        if (app()->environment(['local', 'demo', 'testing'])) {
            $this->call(DemoSeeder::class);
        } else {
            $this->call(LiveSeeder::class);
        }
    }
}
