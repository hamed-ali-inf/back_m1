<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['department_id', 'name', 'email'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
