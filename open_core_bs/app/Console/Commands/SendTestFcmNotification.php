<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Console\Command;

class SendTestFcmNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test {email : User email to send test notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test FCM notification to a user by email';

    /**
     * Execute the console command.
     */
    public function handle(FcmNotificationService $fcmService): int
    {
        $email = $this->argument('email');

        // Find user by email
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ User with email '{$email}' not found.");

            return Command::FAILURE;
        }

        $this->info("📱 Found user: {$user->getFullName()} (ID: {$user->id})");

        // Get user's active FCM tokens
        $fcmTokens = FcmToken::where('user_id', $user->id)
            ->active()
            ->get();

        if ($fcmTokens->isEmpty()) {
            $this->warn('⚠️  No active FCM tokens found for this user.');
            $this->info('The user needs to login to the app to register their device token.');

            return Command::FAILURE;
        }

        $this->info("📱 Found {$fcmTokens->count()} active device(s):");
        foreach ($fcmTokens as $token) {
            $this->line("   • {$token->device_name} ({$token->device_type}) - Device ID: {$token->device_id}");
        }

        $this->newLine();

        // Prepare test notification
        $notification = [
            'title' => '🔔 Test Notification',
            'body' => 'This is a test notification from Open Core Connect!',
        ];

        $data = [
            'type' => 'test',
            'message' => 'FCM is working correctly!',
            'timestamp' => now()->toIso8601String(),
        ];

        $options = [
            'priority' => 'high',
            'sound' => 'default',
        ];

        $this->info('📤 Sending test notification...');

        try {
            // Send notification
            $result = $fcmService->sendToUser(
                $user->id,
                $notification,
                $data,
                $options
            );

            $this->newLine();

            if ($result['success']) {
                $this->info('✅ Notification sent successfully!');
                $this->info("   • Sent: {$result['sent']}");
                $this->info("   • Failed: {$result['failed']}");

                if (! empty($result['details'])) {
                    $this->newLine();
                    $this->info('📊 Detailed Results:');
                    foreach ($result['details'] as $detail) {
                        $status = $detail['success'] ? '✅' : '❌';
                        $deviceInfo = "{$detail['device_id']} ({$detail['device_type']})";
                        $this->line("   {$status} {$deviceInfo}");

                        if (! $detail['success'] && isset($detail['error'])) {
                            $this->line("      Error: {$detail['error']}");
                        }
                    }
                }

                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send notification.');
                $this->error("   Message: {$result['message']}");

                if (! empty($result['details'])) {
                    $this->newLine();
                    $this->info('📊 Detailed Results:');
                    foreach ($result['details'] as $detail) {
                        if (! $detail['success']) {
                            $this->line("   ❌ {$detail['device_id']} ({$detail['device_type']})");
                            if (isset($detail['error'])) {
                                $this->line("      Error: {$detail['error']}");
                            }
                        }
                    }
                }

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Exception occurred: {$e->getMessage()}");
            $this->error('   Stack trace:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
