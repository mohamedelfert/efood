<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSchedule extends Model
{
    protected $fillable = [
        'branch_id',
        'day',
        'opening_time',
        'closing_time',
        'is_24_hours'
    ];

    protected $casts = [
        'day' => 'integer',
        'is_24_hours' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the schedule
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$this->day] ?? 'Unknown';
    }

    /**
     * Check if the schedule is currently open
     */
    public function isOpen(): bool
    {
        if ($this->is_24_hours) {
            return true;
        }

        $now = now();
        $currentDay = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        return $this->day == $currentDay && 
               $currentTime >= $this->opening_time && 
               $currentTime <= $this->closing_time;
    }

    /**
     * Scope to get schedules for a specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get schedules for a specific day
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Scope to get 24-hour schedules
     */
    public function scopeOpen24Hours($query)
    {
        return $query->where('is_24_hours', true);
    }
}