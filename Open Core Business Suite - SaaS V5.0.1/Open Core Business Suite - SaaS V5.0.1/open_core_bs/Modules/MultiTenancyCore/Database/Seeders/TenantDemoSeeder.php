<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\TenantDatabase;
use Spatie\Permission\Models\Role;

class TenantDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create tenant role if it doesn't exist
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);

        // Create demo tenant user
        $tenantUser = User::updateOrCreate(
            ['email' => 'tenant@demo.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Tenant',
                'password' => Hash::make('password123'),
                'phone' => '1234567890',
                'status' => 'active',
                'email_verified_at' => now(),
                'code' => 'TNT-001',
            ]
        );

        // Assign tenant role
        $tenantUser->assignRole('tenant');

        // Create tenant record
        $tenant = Tenant::updateOrCreate(
            ['email' => 'tenant@demo.com'],
            [
                'name' => 'Demo Company Ltd',
                'subdomain' => 'demo-company',
                'phone' => '1234567890',
                'address' => '123 Demo Street',
                'city' => 'Demo City',
                'state' => 'DC',
                'postal_code' => '12345',
                'status' => 'active',
                'trial_ends_at' => Carbon::now()->addDays(14),
                'has_used_trial' => true,
            ]
        );

        // Get a plan (assuming plans are already seeded)
        $plan = Plan::where('is_active', true)->first();

        if ($plan) {
            // Create subscription
            $subscription = Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'trial_ends_at' => Carbon::now()->addDays(14),
                    'starts_at' => Carbon::now(),
                    'ends_at' => Carbon::now()->addMonth(),
                    'amount' => $plan->price ?? 0,
                    'currency' => $plan->currency ?? 'USD',
                ]
            );

            // Create a demo payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => 0, // Trial period
                'currency' => 'USD',
                'payment_method' => 'trial',
                'status' => 'approved',
                'reference_number' => 'TRIAL-'.strtoupper(uniqid()),
                'invoice_number' => 'INV-'.date('Ym').'-00001',
                'description' => 'Trial period - '.$plan->name,
                'paid_at' => now(),
                'approved_at' => now(),
                'approved_by_id' => 1, // Assuming admin user ID is 1
            ]);

            // Create tenant database record (not provisioned)
            TenantDatabase::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'database_name' => 'tenant_demo_company',
                    'username' => 'tenant_demo',
                    'encrypted_password' => encrypt('demo_password'),
                    'provisioning_status' => 'pending',
                    'host' => 'localhost',
                    'port' => 3306,
                ]
            );
        }

        $this->command->info('Tenant demo user created:');
        $this->command->info('Email: tenant@demo.com');
        $this->command->info('Password: password123');
        $this->command->info('Company: Demo Company Ltd');
        $this->command->info('Status: Active (14-day trial)');
    }
}
