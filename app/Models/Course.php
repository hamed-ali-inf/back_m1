<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'level',
        'teacher_id',
    ];

    /**
     * علاقة الدورة بالأستاذ
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * علاقة الدورة بالجداول الزمنية
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}

