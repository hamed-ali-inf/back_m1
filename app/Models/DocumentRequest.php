<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    protected $fillable = [
        'student_id',
        'document_type',
        'status',
    ];

    /**
     * علاقة طلب الوثيقة بالطالب
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
