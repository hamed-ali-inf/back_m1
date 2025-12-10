<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'sender_id',
        'title',
        'content',
        'target_type',
        'target_id',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
