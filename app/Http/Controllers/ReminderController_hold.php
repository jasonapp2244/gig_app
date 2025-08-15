<?php
namespace App\Http\Controllers;
// use app\FirebaseNotificationService;
use App\Models\User;
use App\Models\Reminder;
use Illuminate\Support\Facades\Request;
use App\Services\FirebaseNotificationService;

class ReminderController extends Controller {
    public function storeToken(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string'
        ]);

        User::updateOrCreate(
            ['user_id' => $request->user_id],
            ['device_token' => $request->token]
        );

        return response()->json(['success' => true]);
    }

    public function checkPendingReminders() {
        $now = now()->format('Y-m-d H:i:00');
        $pendingReminders = Reminder::where('reminder_date_time', $now)
                                    ->where('is_sent', 0)
                                    ->where('reminder_status', 1)
                                    ->get();

        foreach ($pendingReminders as $reminder) {
            (new FirebaseNotificationService())->sendReminderNotification(
                $reminder->user_id,
                "Reminder: Task #{$reminder->task_id}",
                "Your task is due now!"
            );

            $reminder->update(['is_sent' => 1]);
        }

        return response()->json(['sent' => $pendingReminders->count()]);
    }
}
