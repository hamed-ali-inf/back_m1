<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'email',
        // أي حقول أخرى لديك في جدول students
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
