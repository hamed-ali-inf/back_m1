<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['institute_id', 'name'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}

