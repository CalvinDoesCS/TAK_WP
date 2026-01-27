<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\Chat\NewChatMessage;
use App\Notifications\Leave\LeaveApproved;
use App\Notifications\Leave\LeaveRejected;
use App\Notifications\Leave\LeaveRequestSubmitted;
use Illuminate\Console\Command;
use Modules\OCConnect\App\Models\ChatMessage;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test 
                            {email : Email or user ID of the recipient}
                            {type : Notification type (chat, leave-submitted, leave-approved, leave-rejected)}
                            {--show-data : Show the notification data that would be sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emailOrId = $this->argument('email');
        $type = $this->argument('type');
        $showData = $this->option('show-data');

        // Find user
        $user = is_numeric($emailOrId)
            ? User::find($emailOrId)
            : User::where('email', $emailOrId)->first();

        if (! $user) {
            $this->error("User not found: {$emailOrId}");

            return 1;
        }

        $this->info("Sending test notification to: {$user->email} ({$user->getFullName()})");
        $this->info("Notification type: {$type}");
        $this->newLine();

        try {
            $notification = $this->createTestNotification($type, $user);

            if (! $notification) {
                $this->error("Unknown notification type: {$type}");
                $this->info('Available types: chat, leave-submitted, leave-approved, leave-rejected');

                return 1;
            }

            // Show notification data if requested
            if ($showData) {
                $this->showNotificationData($notification, $user);
            }

            // Send notification
            $user->notify($notification);

            $this->info('✅ Test notification sent successfully!');
            $this->newLine();

            // Show which channels were used
            $channels = $notification->via($user);
            $this->info('Channels used: '.implode(', ', $channels));

            // Check user's preferences
            $this->newLine();
            $this->info("User's notification preferences:");
            $preferences = $user->notificationPreferences;

            if ($preferences->isEmpty()) {
                $this->warn('No custom preferences set - using defaults');
            } else {
                foreach ($preferences as $pref) {
                    $enabled = [];
                    if ($pref->fcm_enabled) {
                        $enabled[] = 'FCM';
                    }
                    if ($pref->mail_enabled) {
                        $enabled[] = 'Email';
                    }
                    if ($pref->database_enabled) {
                        $enabled[] = 'Database';
                    }
                    if ($pref->broadcast_enabled) {
                        $enabled[] = 'Broadcast';
                    }

                    $this->line("  {$pref->notification_type}: ".implode(', ', $enabled));
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to send notification: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Create a test notification based on type
     */
    private function createTestNotification(string $type, User $user)
    {
        return match ($type) {
            'chat' => $this->createChatNotification($user),
            'leave-submitted' => $this->createLeaveSubmittedNotification($user),
            'leave-approved' => $this->createLeaveApprovedNotification($user),
            'leave-rejected' => $this->createLeaveRejectedNotification($user),
            default => null,
        };
    }

    /**
     * Create a test chat notification
     */
    private function createChatNotification(User $user)
    {
        // Create a mock chat message
        $message = new ChatMessage([
            'id' => 999999,
            'chat_id' => 1,
            'user_id' => auth()->id() ?? 1,
            'content' => 'This is a test notification from the notification:test command',
            'message_type' => 'text',
            'created_at' => now(),
        ]);

        // Set sender relationship
        $message->setRelation('sender', $user);

        return new NewChatMessage($message);
    }

    /**
     * Create a test leave submitted notification
     */
    private function createLeaveSubmittedNotification(User $user)
    {
        $leave = new LeaveRequest([
            'id' => 999999,
            'user_id' => $user->id,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'total_days' => 3,
            'user_notes' => 'Test leave request from notification:test command',
            'status' => 'pending',
        ]);

        $leave->setRelation('user', $user);

        return new LeaveRequestSubmitted($leave);
    }

    /**
     * Create a test leave approved notification
     */
    private function createLeaveApprovedNotification(User $user)
    {
        $leave = new LeaveRequest([
            'id' => 999999,
            'user_id' => $user->id,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'total_days' => 3,
            'user_notes' => 'Test leave request - approved',
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => auth()->id() ?? 1,
        ]);

        $leave->setRelation('user', $user);
        $leave->setRelation('approvedBy', auth()->user() ?? $user);

        return new LeaveApproved($leave);
    }

    /**
     * Create a test leave rejected notification
     */
    private function createLeaveRejectedNotification(User $user)
    {
        $leave = new LeaveRequest([
            'id' => 999999,
            'user_id' => $user->id,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'total_days' => 3,
            'user_notes' => 'Test leave request - rejected',
            'approval_notes' => 'This is a test rejection from the notification:test command',
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by_id' => auth()->id() ?? 1,
        ]);

        $leave->setRelation('user', $user);
        $leave->setRelation('rejectedBy', auth()->user() ?? $user);

        return new LeaveRejected($leave);
    }

    /**
     * Show notification data that would be sent
     */
    private function showNotificationData($notification, $user)
    {
        $this->info('Notification Data:');
        $this->newLine();

        // Get FCM data if available
        if (method_exists($notification, 'toFcm')) {
            $fcmData = $notification->toFcm($user);
            $this->line('FCM Notification:');
            $this->line('  Title: '.($fcmData['title'] ?? 'N/A'));
            $this->line('  Body: '.($fcmData['body'] ?? 'N/A'));
            if (isset($fcmData['data'])) {
                $this->line('  Data: '.json_encode($fcmData['data'], JSON_PRETTY_PRINT));
            }
            $this->newLine();
        }

        // Get Database data if available
        if (method_exists($notification, 'toDatabase')) {
            $dbData = $notification->toDatabase($user);
            $this->line('Database Notification:');
            $this->line(json_encode($dbData, JSON_PRETTY_PRINT));
            $this->newLine();
        }

        // Get Mail data if available
        if (method_exists($notification, 'toMail')) {
            $this->line('Email Notification: Will be sent');
            $this->newLine();
        }
    }
}
