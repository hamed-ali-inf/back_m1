<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'email',
        'phone'
    ];

    /**
     * Get the departments for the institute.
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get the students for the institute.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
