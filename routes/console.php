<?php
use Illuminate\Support\Facades\Schedule;

//midnight reminder command
Schedule::command('reminders:daily-send')->dailyAt('05:27');
