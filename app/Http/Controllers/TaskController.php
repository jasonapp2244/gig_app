<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Employer;
use App\Models\Reminder;
use App\Models\TaskPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;
use PhpParser\Node\NullableType;

class TaskController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = Task::where('user_id', Auth::id());


            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {

                $query->whereIn('status', ['pending', 'completed']);
            }

            $tasks = $query->orderBy('schedule_date', 'asc')->get();


            foreach ($tasks as $task) {
                if ($task->start_time && $task->end_time && $task->status !== 'completed') {
                    $task->status = 'completed';
                    $task->save();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Task list fetched successfully.',
                'tasks' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Hold
    // public function store(Request $request)
    // {
    //     try {
    //         $user = Auth::user();

    //         $validator = Validator::make($request->all(), [
    //             'employer' => 'nullable|string|max:255',
    //             'job_title' => 'required|string|max:255',
    //             'job_type' => 'nullable|in:off,day,night',
    //             'vocation',
    //             'job_category' => 'nullable|in:hourly,monthly,yearly',
    //             'location' => 'nullable|string|max:255',
    //             'supervisor_contact_number' => 'nullable|string|max:50',
    //             'working_hours' => 'nullable|string|max:50',
    //             'pay' => 'nullable|numeric',
    //             'straight_time' => 'nullable|string|max:50',
    //             'start_date' => 'nullable|date',
    //             'end_date' => 'nullable|date|after_or_equal:start_date',
    //             'notes' => 'nullable|string',
    //             'start_time' => 'nullable|date_format:H:i',
    //             'end_time' => 'nullable|date_format:H:i',
    //             'task_date_time' => 'nullable|date',
    //             'reminder_checkbox' => 'nullable|boolean',
    //             'reminder_date' => 'nullable|date',
    //             'make_hole' => 'nullable|boolean',

    //             // extra fields if make_hole is true
    //             'guaranteed_steady_hours' => 'nullable|numeric',
    //             'flop_hours' => 'nullable|numeric',
    //             'avg_hours' => 'nullable|numeric',
    //             'bonus_pay' => 'nullable|numeric',
    //             'travel_location' => 'nullable|string|max:255',
    //             'travel_hours' => 'nullable|numeric',
    //             'travel_pay' => 'nullable|numeric',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    //         }

    //         if (!empty($request->task_date_time)) {
    //             $existingTask = Task::where('user_id', $user->id)
    //                 ->where('task_date_time', $request->task_date_time)
    //                 ->first();

    //             if ($existingTask) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => 'You already have a task at this date and time. Please choose another time.'
    //                 ]);
    //             }
    //         }

    //         // Parse date & calculate end time
    //         $taskStart = Carbon::parse($request->task_date_time);
    //         $workingMinutes = $request->working_hours * 60;
    //         $taskEnd = $taskStart->copy()->addMinutes($workingMinutes);
    //         // Create or get employer and get ID
    //         $employerId = null;
    //         $employerName = null;

    //         if ($request->filled('employer')) {
    //             $employerName = trim($request->employer);

    //             $employer = Employer::firstOrCreate(
    //                 ['employer_name' => $employerName, 'user_id' => $user->id],
    //                 ['status' => true]
    //             );

    //             $employerId = $employer->id;
    //         }

    //         $taskData = [
    //             'user_id' => $user->id,
    //             'employer_id' => $employerId,
    //             'employer' => $employerName,
    //             'job_title' => $request->job_title,
    //             'job_type' => $request->job_type,
    //             'job_category' => $request->job_category,
    //             'location' => $request->location,
    //             'supervisor_contact_number' => $request->supervisor_contact_number,
    //             'working_hours' => $request->working_hours,
    //             'pay' => $request->pay,
    //             'straight_time' => $request->straight_time,
    //             'start_date' => $request->start_date,
    //             'end_date' => $request->end_date,
    //             'notes' => $request->notes,
    //             'start_time' => $request->start_time,
    //             'end_time' => $request->end_time,
    //             'task_date_time' => $taskStart,
    //             'task_end_date_time' => $taskEnd,

    //             'status' => match (true) {
    //                 $taskStart->isSameDay(Carbon::today()) => 'ongoing',
    //                 $taskStart->greaterThan(Carbon::now()) && $taskStart->isFuture() => 'pending',
    //                 $taskStart->lessThan(Carbon::now()) => 'completed',
    //                 default => 'pending',
    //             },

    //             'make_hole' => $request->make_hole ?? false,
    //         ];


    //         if ($request->make_hole) {
    //             $taskData = array_merge($taskData, [
    //                 'guaranteed_steady_hours' => $request->guaranteed_steady_hours,
    //                 'flop_hours' => $request->flop_hours,
    //                 'avg_hours' => $request->avg_hours,
    //                 'bonus_pay' => $request->bonus_pay,
    //                 'travel_location' => $request->travel_location,
    //                 'travel_hours' => $request->travel_hours,
    //                 'travel_pay' => $request->travel_pay,
    //             ]);
    //         }

    //         $task = Task::create($taskData);

    //         // Save task payment if given
    //         if ($request->filled('pay')) {
    //             TaskPayment::firstOrCreate(
    //                 [
    //                     'user_id' => $user->id,
    //                     'task_id' => $task->id,
    //                 ],
    //                 [
    //                     'payment_title' => $request->employer,
    //                     'payment' => $request->pay,
    //                     'payment_status' => 'pending',
    //                 ]
    //             );
    //         }

    //         // Save reminder if set
    //         if ($request->reminder_checkbox && !empty($request->reminder_date)) {
    //             Reminder::create([
    //                 'user_id' => $user->id,
    //                 'task_id' => $task->id,
    //                 'reminder_date_time' => $request->reminder_date,
    //                 'is_sent' => false,
    //             ]);
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Task created successfully.',
    //             'task' => $task,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    //store tasks
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'employer' => 'nullable|string|max:255',
                'job_title' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'supervisor_contact_number' => 'nullable|string|max:50',
                'working_hours' => 'nullable|string|max:50',
                'pay' => 'nullable|numeric',
                // 'straight_time' => 'nullable|string|max:50',
                'task_date_time' => 'nullable|date',
                // 'reminder_checkbox' => 'nullable|boolean',
                // 'reminder_date' => 'nullable|date',
                'notes' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            if (!empty($request->task_date_time)) {
                $existingTask = Task::where('user_id', $user->id)
                    ->where('task_date_time', $request->task_date_time)
                    ->first();

                if ($existingTask) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You already have a task at this date and time. Please choose another time.'
                    ]);
                }
            }

            // Parse date and working minutes
            $taskStart = Carbon::parse($request->task_date_time);
            $workingMinutes = $request->working_hours * 60;
            $taskEnd = $taskStart->copy()->addMinutes($workingMinutes);

            $now = Carbon::now();
            $status = match (true) {
                $taskStart->isSameDay($now) => 'ongoing',
                $taskStart->greaterThan($now) => 'pending',
                $taskEnd->lessThanOrEqualTo($now) => 'completed',
            };
            $employerId = null;
            $employerName = null;

            if ($request->filled('employer')) {
                $employerName = trim($request->employer);

                $employer = Employer::firstOrCreate(
                    ['employer_name' => $employerName, 'user_id' => $user->id],
                    ['status' => true]
                );

                $employerId = $employer->id;
            }

            // Prepare task data
            $taskData = [
                'user_id' => $user->id,
                'employer_id' => $employerId,
                'employer' => $employerName,
                'job_title' => $request->job_title,
                'location' => $request->location,
                'supervisor_contact_number' => $request->supervisor_contact_number,
                'working_hours' => $request->working_hours,
                'pay' => $request->pay,
                'task_date_time' => $request->task_date_time ?? now(),
                'task_end_date_time' => $taskEnd ?? now(),
                'notes' => $request->notes,
                'status' => $status,

            ];

            $task = Task::create($taskData);

            // Save task payment if given
            if ($request->filled('pay')) {
                TaskPayment::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'task_id' => $task->id,
                    ],
                    [
                        'payment_title' => $request->job_title,
                        'payment' => $request->pay,
                        'payment_status' => 'pending',
                    ]
                );
            }

            // Save reminder if set
            // if ($request->reminder_checkbox && !empty($taskEnd)) {
            //     Reminder::create([
            //         'user_id' => $user->id,
            //         'task_id' => $task->id,
            //         'reminder_date_time' => $taskEnd,
            //         'is_sent' => false,
            //     ]);
            // }

            return response()->json([
                'status' => true,
                'message' => 'Task created successfully.',
                'task' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //update method hold
    //  public function update(Request $request, $id)
    //     {
    //         try {
    //             $user = Auth::user();

    //             $validator = Validator::make($request->all(), [
    //                 'employer' => 'nullable|string|max:255',
    //                 'job_title' => 'required|string|max:255',
    //                 'job_type' => 'nullable|in:off,day,night',
    //                 'job_category' => 'nullable|in:hourly,monthly,yearly',
    //                 'location' => 'nullable|string|max:255',
    //                 'supervisor_contact_number' => 'nullable|string|max:50',
    //                 'working_hours' => 'nullable|string|max:50',
    //                 'pay' => 'nullable|numeric',
    //                 'straight_time' => 'nullable|string|max:50',
    //                 'start_date' => 'nullable|date',
    //                 'end_date' => 'nullable|date|after_or_equal:start_date',
    //                 'notes' => 'nullable|string',
    //                 'start_time' => 'nullable|date_format:H:i',
    //                 'end_time' => 'nullable|date_format:H:i',
    //                 'task_date_time' => 'nullable|date',
    //                 'reminder_checkbox' => 'nullable|boolean',
    //                 'reminder_date' => 'nullable|date',
    //                 'make_hole' => 'nullable|boolean',

    //                 // extra fields if make_hole is true
    //                 'guaranteed_steady_hours' => 'nullable|numeric',
    //                 'flop_hours' => 'nullable|numeric',
    //                 'avg_hours' => 'nullable|numeric',
    //                 'bonus_pay' => 'nullable|numeric',
    //                 'travel_location' => 'nullable|string|max:255',
    //                 'travel_hours' => 'nullable|numeric',
    //                 'travel_pay' => 'nullable|numeric',
    //             ]);

    //             if ($validator->fails()) {
    //                 return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    //             }

    //             $task = Task::where('id', $id)->where('user_id', $user->id)->first();
    //             if (!$task) {
    //                 return response()->json(['status' => false, 'message' => 'Task not found.'], 404);
    //             }

    //             // Parse task date & calculate end
    //             $taskStart = Carbon::parse($request->task_date_time);
    //             $workingMinutes = $request->working_hours * 60;
    //             $taskEnd = $taskStart->copy()->addMinutes($workingMinutes);

    //             $employerId = null;
    //             $employerName = null;

    //             if ($request->filled('employer')) {
    //                 $employerName = trim($request->employer);

    //                 $employer = Employer::firstOrCreate(
    //                     ['employer_name' => $employerName, 'user_id' => $user->id],
    //                     ['status' => true]
    //                 );

    //                 $employerId = $employer->id;
    //             }

    //             $updateData = [
    //                 'employer_id' => $employerId,
    //                 'employer' => $employerName,
    //                 'job_title' => $request->job_title,
    //                 'job_type' => $request->job_type,
    //                 'job_category' => $request->job_category,
    //                 'location' => $request->location,
    //                 'supervisor_contact_number' => $request->supervisor_contact_number,
    //                 'working_hours' => $request->working_hours,
    //                 'pay' => $request->pay,
    //                 'straight_time' => $request->straight_time,
    //                 'start_date' => $request->start_date,
    //                 'end_date' => $request->end_date,
    //                 'notes' => $request->notes,
    //                 'start_time' => $request->start_time,
    //                 'end_time' => $request->end_time,
    //                 'task_date_time' => $taskStart,
    //                 'task_end_date_time' => $taskEnd,
    //                 'status' => match (true) {
    //                     $taskStart->isToday() => 'ongoing',
    //                     $taskStart->isPast() => 'completed',
    //                     $taskStart->isFuture() => 'pending',
    //                     default => 'pending',
    //                 },
    //                 'make_hole' => $request->make_hole ?? false,
    //             ];

    //             if ($request->make_hole) {
    //                 $updateData = array_merge($updateData, [
    //                     'guaranteed_steady_hours' => $request->guaranteed_steady_hours,
    //                     'flop_hours' => $request->flop_hours,
    //                     'avg_hours' => $request->avg_hours,
    //                     'bonus_pay' => $request->bonus_pay,
    //                     'travel_location' => $request->travel_location,
    //                     'travel_hours' => $request->travel_hours,
    //                     'travel_pay' => $request->travel_pay,
    //                 ]);
    //             }

    //             $task->update($updateData);

    //             // Update or create reminder
    //             if ($request->reminder_checkbox && !empty($request->reminder_date)) {
    //                 Reminder::updateOrCreate(
    //                     ['user_id' => $user->id, 'task_id' => $task->id],
    //                     ['reminder_date_time' => $request->reminder_date, 'is_sent' => false]
    //                 );
    //             }

    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Task updated successfully.',
    //                 'task' => $task
    //             ]);
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Something went wrong.',
    //                 'error' => $e->getMessage()
    //             ], 500);
    //         }
    //     }


// Update task method
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'employer' => 'nullable|string|max:255',
                'job_title' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'supervisor_contact_number' => 'nullable|string|max:50',
                'working_hours' => 'nullable|string|max:50',
                'pay' => 'nullable|numeric',
                // 'straight_time' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'task_date_time' => 'nullable|date',
                // 'reminder_checkbox' => 'nullable|boolean',
                // 'reminder_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $task = Task::where('id', $id)->where('user_id', $user->id)->first();
            if (!$task) {
                return response()->json(['status' => false, 'message' => 'Task not found.'], 404);
            }

            $taskStart = Carbon::parse($request->task_date_time);
            $workingMinutes = $request->working_hours * 60;
            $taskEnd = $taskStart->copy()->addMinutes($workingMinutes);

            $now = Carbon::now();
            $status = match (true) {
                $taskStart->isSameDay($now) => 'ongoing',
                $taskStart->greaterThan($now) => 'pending',
                $taskEnd->lessThanOrEqualTo($now) => 'completed',
            };

            $employerId = null;
            $employerName = null;

            if ($request->filled('employer')) {
                $employerName = trim($request->employer);

                $employer = Employer::firstOrCreate(
                    ['employer_name' => $employerName, 'user_id' => $user->id],
                    ['status' => true]
                );

                $employerId = $employer->id;
            }

            $updateData = [
                'employer_id' => $employerId,
                'employer' => $employerName,
                'job_title' => $request->job_title,
                'location' => $request->location,
                'supervisor_contact_number' => $request->supervisor_contact_number,
                'working_hours' => $request->working_hours,
                'pay' => $request->pay,
                // 'straight_time' => $request->straight_time,
                'notes' => $request->notes,
                'task_date_time' => $taskStart,
                'task_end_date_time' => $taskEnd,
                'status' => $status
            ];

            $task->update($updateData);


            // Update or create reminder
            // if ($request->reminder_checkbox && !empty($taskEnd)) {
            //     Reminder::updateOrCreate(
            //         ['user_id' => $user->id, 'task_id' => $task->id],
            //         ['reminder_date_time' => $taskEnd, 'is_sent' => false]
            //     );
            // }

            return response()->json([
                'status' => true,
                'message' => 'Task updated successfully.',
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Filter tasks by status
    public function filterByStatus(Request $request)
    {
        try {

            $userId = Auth::id();

            $query = Task::where('user_id', $userId);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                $query->whereIn('status', ['pending', 'ongoing', 'completed']);
            }

            $tasks = $query->orderBy('task_date_time', 'asc')->get();

            //Update status based on task_date_time & task_end_date_time
            foreach ($tasks as $task) {
                $now = now();

                $start = $task->task_date_time ? Carbon::parse($task->task_date_time)->setTimezone(config('app.timezone')) : null;
                $end = $task->task_end_date_time ? Carbon::parse($task->task_end_date_time)->setTimezone(config('app.timezone')) : null;

                if ($start && $end) {
                    if ($now->greaterThanOrEqualTo($end) && $task->status !== 'completed') {
                        $task->status = 'completed';
                        $task->save();
                    } elseif ($start->isToday() && $now->lessThan($end) && $task->status !== 'ongoing') {
                        $task->status = 'ongoing';
                        $task->save();
                    } elseif ($start->isFuture() && $task->status !== 'pending') {
                        $task->status = 'pending';
                        $task->save();
                    }
                }
            }

            //Refresh tasks
            $allTasks = Task::with('employerRelation')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'ongoing', 'completed'])
                ->get();

            $total = $allTasks->count();
            //
            $employerAllSummary = $allTasks->groupBy('employer_id')->map(function ($tasks, $employerId) {
                $total = $tasks->count();
                $completed = $tasks->where('status', 'completed')->count();
                $ongoing = $tasks->where('status', 'ongoing')->count();
                $pending = $tasks->where('status', 'pending')->count();

                $percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

                $employerName = optional($tasks->first())->employer ?? 'N/A';

                $dates = $tasks->pluck('task_date_time')->filter();

                return [
                    'employer_id' => $employerId,
                    'employer_name' => $employerName,
                    'total' => $total,
                    'completed' => $completed,
                    'ongoing' => $ongoing,
                    'percentage' => $percentage,
                    'summary_text' => "{$completed} completed / {$ongoing} ongoing / {$pending} pending / {$total} total ({$percentage}%)",
                    'from_date' => optional($dates->min())->toDateString(),
                    'to_date' => optional($dates->max())->toDateString(),
                ];
            })->values();


            // Employer Status Summary (based on selected status only)
            $employerStatusSummary = [];

            if ($request->filled('status')) {
                $status = $request->status;

                $statusFilteredTasks = $allTasks->where('status', $status);

                $allEmployerTasks = $allTasks->groupBy('employer_id');

                $employerStatusSummary = $statusFilteredTasks->groupBy('employer_id')->map(function ($tasks, $employerId) use ($status, $allEmployerTasks) {
                    $statusCount = $tasks->count();
                    $totalCount = $allEmployerTasks[$employerId]->count();
                    $employerName = optional($tasks->first())->employer ?? 'N/A';
                    $dates = $tasks->pluck('task_date_time')->filter();
                    $percentage = $totalCount > 0 ? round(($statusCount / $totalCount) * 100, 2) : 0;
                    return [
                        'employer_id' => $employerId,
                        'employer_name' => $employerName,
                        'status' => $status,
                        'count' => $statusCount,
                        'total' => $totalCount,
                        'summary_text' => "{$statusCount}/{$totalCount} ({$status})",
                        'percentage' => "{$percentage}%",
                        'from_date' => optional($dates->min())->toDateString(),
                        'to_date' => optional($dates->max())->toDateString(),
                    ];
                })->values();
            }


            return response()->json([
                'status' => true,
                'message' => 'Task list fetched successfully.',
                'tasks' => $tasks,
                'total_tasks' => $total,
                'employer_all_summary' => $employerAllSummary,
                'employer_status_summary' => $employerStatusSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public $employerRelation;
    protected $appends = ['employer'];
    public function getEmployerAttribute()
    {
        return $this->employerRelation ? $this->employerRelation->employer_name : null;
    }


    // public function filterByStatus(Request $request)
    // {
    //     try {
    //         $query = Task::where('user_id', Auth::id());

    //         if ($request->filled('status')) {
    //             $query->where('status', $request->status);
    //         } else {
    //             $query->whereIn('status', ['pending', 'ongoing', 'completed']);
    //         }

    //         $tasks = $query->orderBy('task_date_time', 'asc')->get();

    //         // ✅ Update status based on task_date_time and task_end_date_time
    //         foreach ($tasks as $task) {
    //             $now = now();

    //             $start = $task->task_date_time ? \Carbon\Carbon::parse($task->task_date_time) : null;
    //             $end = $task->task_end_date_time ? \Carbon\Carbon::parse($task->task_end_date_time) : null;

    //             if ($start && $end) {
    //                 if ($now->greaterThanOrEqualTo($end) && $task->status !== 'completed') {
    //                     $task->status = 'completed';
    //                     $task->save();
    //                 } elseif ($start->isToday() && $task->status !== 'ongoing') {
    //                     $task->status = 'ongoing';
    //                     $task->save();
    //                 } elseif ($start->isFuture() && $task->status !== 'pending') {
    //                     $task->status = 'pending';
    //                     $task->save();
    //                 }
    //             }
    //         }

    //         // 🔁 Re-fetch after updating statuses
    //         $allTasks = Task::where('user_id', Auth::id())
    //             ->whereIn('status', ['pending', 'ongoing', 'completed'])
    //             ->get();

    //         $total = $allTasks->count();
    //         $summary = [];

    //         if ($request->filled('status')) {
    //             $status = $request->status;
    //             $filteredTasks = $allTasks->where('status', $status);
    //             $count = $filteredTasks->count();

    //             $startDates = $filteredTasks->pluck('task_date_time')->filter();

    //             $summary[] = [
    //                 'status' => $status,
    //                 'count' => $count,
    //                 'total' => $total,
    //                 'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
    //                 'summary_text' => "{$count}/{$total} (" . ($total > 0 ? round(($count / $total) * 100, 2) : 0) . "%)",
    //                 'from_date' => optional($startDates->min())->toDateString(),
    //                 'to_date' => optional($startDates->max())->toDateString(),
    //             ];
    //         } else {
    //             $summary = $allTasks->groupBy('status')->map(function ($items, $status) use ($total) {
    //                 $count = $items->count();
    //                 $startDates = $items->pluck('task_date_time')->filter();
    //                 $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

    //                 return [
    //                     'status' => $status,
    //                     'count' => $count,
    //                     'total' => $total,
    //                     'percentage' => $percentage,
    //                     'summary_text' => "{$count}/{$total} ({$percentage}%)",
    //                     'from_date' => optional($startDates->min())->toDateString(),
    //                     'to_date' => optional($startDates->max())->toDateString(),
    //                 ];
    //             })->values();
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Task list fetched successfully.',
    //             'tasks' => $tasks,
    //             'summary' => $summary,
    //             'total_tasks' => $total
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function markCompleted($id)
    {
        try {
            $task = Task::where('id', $id)->where('user_id', Auth::id())->first();
            if (!$task) {
                return response()->json(['status' => false, 'message' => 'Task not found.']);
            }

            $task->status = 'completed';
            $task->save();

            return response()->json(['status' => true, 'message' => 'Task marked as completed.', 'task' => $task]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Delete task method
    public function deleteTask($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.'
            ], 404);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.'
        ], 200);
    }
}
