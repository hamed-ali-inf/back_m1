<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'day',
        'start_time',
        'end_time',
        'classroom',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * علاقة الجدول الزمني بالدورة
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * علاقة الجدول الزمني بالأستاذ (عبر الدورة)
     */
    public function teacher()
    {
        return $this->hasOneThrough(
            \App\Models\Teacher::class,
            Course::class,
            'id',        // Foreign key on courses table
            'id',        // Foreign key on teachers table
            'course_id', // Local key on schedules table
            'teacher_id' // Local key on courses table
        );
    }
}
