<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'level',
        'department_id',
        'institute_id',
        'user_id'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
