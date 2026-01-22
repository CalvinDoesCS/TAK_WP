<?php

namespace Database\Seeders;

use App\Enums\NotificationPreferenceType;
use App\Helpers\NotificationPreferenceHelper;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationPreferenceSeeder extends Seeder
{
    /**
     * Seed default notification preferences for all users.
     *
     * This seeder creates default notification preferences for all existing users
     * based on the default channel configurations for each notification type.
     *
     * It's idempotent - can be run multiple times safely.
     * It will skip users who already have preferences set.
     */
    public function run(): void
    {
        $this->command->info('Starting notification preferences seeding...');

        try {
            DB::beginTransaction();

            // Get all active users
            $users = User::all();
            $this->command->info("Found {$users->count()} users");

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($users as $user) {
                // Check if user already has preferences
                $existingPreferencesCount = NotificationPreference::where('user_id', $user->id)->count();

                if ($existingPreferencesCount > 0) {
                    $this->command->warn("User {$user->email} already has {$existingPreferencesCount} preferences - skipping");
                    $skippedCount++;

                    continue;
                }

                $this->command->info("Creating preferences for user: {$user->email}");

                // Create preferences for each notification type using defaults
                foreach (NotificationPreferenceType::cases() as $type) {
                    $defaults = NotificationPreferenceHelper::getDefaultChannels($type);

                    NotificationPreference::create([
                        'user_id' => $user->id,
                        'notification_type' => $type->value,
                        'fcm_enabled' => $defaults['fcm'] ?? true,
                        'mail_enabled' => $defaults['mail'] ?? false,
                        'database_enabled' => $defaults['database'] ?? true,
                        'broadcast_enabled' => $defaults['broadcast'] ?? false,
                    ]);
                }

                $createdCount++;
                $this->command->info('✓ Created '.count(NotificationPreferenceType::cases())." preferences for {$user->email}");
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('===========================================');
            $this->command->info('Notification Preferences Seeding Complete');
            $this->command->info('===========================================');
            $this->command->info("Users processed: {$users->count()}");
            $this->command->info("Users with new preferences: {$createdCount}");
            $this->command->info("Users skipped (already have preferences): {$skippedCount}");
            $this->command->info('Total preferences created: '.($createdCount * count(NotificationPreferenceType::cases())));
            $this->command->info('===========================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to seed notification preferences: '.$e->getMessage());
            Log::error('Notification preference seeding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
