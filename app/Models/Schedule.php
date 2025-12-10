<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'course_id',
        'day',
        'start_time',
        'end_time',
        'classroom',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
