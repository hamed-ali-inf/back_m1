<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'level',
    ];

    /**
     * علاقة الدورة بالأستاذ
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

<<<<<<< HEAD
    /**
     * علاقة الدورة بالجداول الزمنية
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function files()
    {
        return $this->hasMany(CourseFile::class);
    }
}

