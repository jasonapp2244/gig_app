<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class SendDailyRemindersCommand extends Command
{
    protected $signature = 'reminders:daily-send';
    protected $description = 'Send daily reminders for all tasks at 12:00 AM';

    public function handle()
    {
        // ✅ Ensure it runs only at midnight
        if (now()->format('H:i') !== '00:00') {
            $this->info('Not midnight. Skipping...');
            return Command::SUCCESS;
        }

        $today = now()->toDateString();




        $tasks = Task::whereDate('task_date_time', $today)
            ->where('is_reminder_sent', false)
            ->orderBy('task_date_time', 'asc')
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

            if (!$user || !$user->fcm_token) {
                $failedCount++;
                continue;
            }

            try {
                // dd($task->toArray());
                $firebase->sendNotificationToToken(
                    $user->fcm_token,
                    "Daily Task Reminder",
                    "Your task '{$task->job_title}' is scheduled for today at {$task->task_date_time->format('H:i')}",
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

        $this->info("Daily reminders processed: {$sentCount} sent, {$failedCount} failed out of {$tasks->count()} total");
        Log::info("Daily reminders processed: {$sentCount} sent, {$failedCount} failed out of {$tasks->count()} total");

        return Command::SUCCESS;
    }
}
