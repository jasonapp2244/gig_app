<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TaskPaymentController extends Controller
{


    public function getTasks()
    {

        $tasks_payments = TaskPayment::where('user_id', Auth::id())
            ->where('payment_status', 'pending')
            ->get();
        if ($tasks_payments->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No pending tasks found',
            ], 400);

            }
            return response()->json([
                'status' => true,
                'message' => 'Pending tasks fetched successfully',
                'data' => $tasks_payments,
            ], 200);
    }


    public function getTasksByStatus($task_status)
    {
        $tasks = TaskPayment::where('user_id', Auth::id())
            ->where('payment_status', $task_status)
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No tasks found for the given status',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Tasks fetched successfully',
            'data' => $tasks,
        ], 200);
    }



  public function taskPayment(Request $request)
{
    // Agar id di gayi hai to update hoga, warna create
    $isUpdate = $request->has('id');

    if ($isUpdate) {
        // Update case
        $request->validate([
            'id' => 'required|exists:task_payments,id',
            'payment_status' => 'nullable|in:pending,paid',
        ]);

        $task_payment = TaskPayment::findOrFail($request->id);
        $task_payment->update([
            'payment_status' => $request->payment_status,
        ]);

    } else {
        // Create case
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'payment_title' => 'required|string',
            'payment' => 'required|numeric',
            'payment_status' => 'nullable|in:pending,paid',
        ]);

        $task_payment = TaskPayment::create([
            'user_id' => Auth::id(),
            'task_id' => $request->task_id,
            'payment_title' => $request->payment_title,
            'payment' => $request->payment,
            'payment_status' => $request->payment_status ?? 'paid',
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Record saved successfully',
        'task_payment' => $task_payment,
    ], 200);
}




    public function earningSummary(Request $request)
    {
        $userId = Auth::id();

        $totalPaid = TaskPayment::where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->sum('payment');

        $pendingEarning = TaskPayment::where('user_id', $userId)
            ->where('payment_status', 'pending')
            ->sum('payment');

        $currentYear = Carbon::now()->year;

        $newYearEarning = TaskPayment::where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->whereYear('created_at', $currentYear)
            ->sum('payment');

        return response()->json([
            'status' => true,
            'message' => 'Earning summary fetched successfully',
            'total_balance' => $totalPaid,
            'available_earning' => $totalPaid,
            'pending_earning' => $pendingEarning,
            'new_year_earning' => $newYearEarning,
        ], 200);
    }
}
