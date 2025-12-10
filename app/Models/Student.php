<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'level',
        'group',
        'department_id',
        'institute_id',
    ];

    /**
     * علاقة الطالب بالمستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة الطالب بالقسم
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * علاقة الطالب بالمعهد
     */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * علاقة الطالب بطلبات الوثائق
     */
    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    /**
     * الحصول على الدورات المتعلقة بالطالب (حسب المستوى)
     */
    public function getCourses()
    {
        return Course::where('level', $this->level)->get();
    }

    /**
     * الحصول على الإعلانات المتعلقة بالطالب (حسب المستوى والقسم والمعهد)
     */
    public function getAnnouncements()
    {
        return Announcement::where(function($query) {
            $query->where('level', $this->level)
                  ->orWhere('department_id', $this->department_id)
                  ->orWhere('institute_id', $this->institute_id)
                  ->orWhereNull('level')
                  ->orWhereNull('department_id')
                  ->orWhereNull('institute_id');
        })->get();
    }
}
