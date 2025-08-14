<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    protected $fillable = ['user_id', 'employer_name', 'job_type', 'salary', 'location', 'description', 'employer_image', 'status'];


    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
