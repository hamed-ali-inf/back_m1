<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'department_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function schedules()
    {
        return $this->hasManyThrough(Schedule::class, Course::class);
    }

    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id', 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id', 'user_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'sender_id', 'user_id');
    }
}

