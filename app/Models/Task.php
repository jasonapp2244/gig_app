<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'employer_id',
        'employer',
        'job_title',
        'job_type',
        'job_category',
        'location',
        'supervisor_contact_number',
        'working_hours',
        'pay',
        'straight_time',
        'start_date',
        'end_date',
        'schedule_date',
        'start_time',
        'end_time',
        'task_date_time',
        'task_end_date_time',
        'is_reminder_sent',
        'reminder_sent_at',
        'status',
        'notes',
        'guaranteed_steady_hours',
        'flop_hours',
        'avg_hours',
        'bonus_pay',
        'travel_location',
        'travel_hours',
        'travel_pay',
        'wages',
        'has_entry',
        'is_locked',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pay' => 'decimal:2',
        'guaranteed_steady_hours' => 'decimal:2',
        'flop_hours' => 'decimal:2',
        'avg_hours' => 'decimal:2',
        'bonus_pay' => 'decimal:2',
        'travel_hours' => 'decimal:2',
        'travel_pay' => 'decimal:2',
        'wages' => 'decimal:2',

        'start_date' => 'date',
        'end_date' => 'date',
        'schedule_date' => 'date',

        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'task_date_time' => 'datetime',
        'task_end_date_time' => 'datetime',

        'is_reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',

        'has_entry' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the user that owns the task.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payments related to the task.
     */
    public function taskPayments()
    {
        return $this->hasMany(TaskPayment::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function employerRelation()
    {
        return $this->belongsTo(Employer::class, 'employer_id');
    }
}
