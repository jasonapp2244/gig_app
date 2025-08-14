<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendDailyRemindersCommand extends Command
{
    protected $signature = 'reminders:daily-send';
    protected $description = 'Send daily reminders for all tasks at 12:00 AM';

    public function handle()
    {
        $today = now()->toDateString();

        // Check if notifications_enabled column exists in users table
        $notificationsEnabledColumnExists = Schema::hasColumn('users', 'notifications_enabled');

        // Get today's tasks that haven't been sent reminders
        $tasks = Task::whereDate('task_date_time', $today)
            ->where('is_reminder_sent', false)
            ->orderBy('task_date_time', 'asc')
            ->with('user') // eager load to reduce queries
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks for today.');
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $failedCount = 0;
        $firebase = app(FirebaseService::class);

        foreach ($tasks as $task) {
            $user = $task->user;

            // Skip if no user or FCM token
            if (!$user || !$user->fcm_token) {
                $failedCount++;
                Log::warning("Skipped task {$task->id}: No user or missing FCM token");
                continue;
            }

            // Skip if notifications are disabled
            if (
                $notificationsEnabledColumnExists &&
                ($user->notifications_enabled === 0 || $user->notifications_enabled === false)
            ) {
                $failedCount++;
                Log::info("Skipped task {$task->id} for user {$user->id}: Notifications disabled");
                continue;
            }

            try {
                $firebase->sendNotificationToToken(
                    $user->fcm_token,
                    "Daily Task Reminder",
                    "Your task '{$task->job_title}' is scheduled for today at " . $task->task_date_time->format('h:i A'),
                    [
                        'task_id'   => $task->id,
                        'task_date' => $task->task_date_time->toDateString(),
                        'task_time' => $task->task_date_time->format('H:i')
                    ]
                );

                $task->update([
                    'is_reminder_sent' => true,
                    'reminder_sent_at' => now()
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error("Failed to send reminder for task {$task->id}: " . $e->getMessage());
            }
        }

        $this->info("Daily reminders processed: {$sentCount} sent, {$failedCount} skipped/failed out of {$tasks->count()} total");
        Log::info("Daily reminders processed: {$sentCount} sent, {$failedCount} skipped/failed out of {$tasks->count()} total");

        return Command::SUCCESS;
    }
}
