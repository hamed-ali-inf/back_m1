<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'name'
    ];

    /**
     * Get the institute that owns the department.
     */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * Get the teachers for the department.
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}

