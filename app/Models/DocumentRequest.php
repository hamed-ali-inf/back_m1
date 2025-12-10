<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'status',
        'notes',
        'rejection_reason',
        'approved_at',
        'completed_at',
        'approved_by'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // أنواع الوثائق المطلوبة
    const DOCUMENT_TYPES = [
        'registration_certificate' => 'شهادة التسجيل',
        'transcript' => 'بيان الدرجات (كشف النقاط)',
        'graduation_certificate' => 'شهادة التخرج',
        'study_certificate' => 'إفادة دراسة',
        'other_administrative' => 'وثائق إدارية أخرى'
    ];

    // حالات الطلب
    const STATUSES = [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'completed' => 'مكتمل (تم تسليمه)',
        'cancelled' => 'ملغي'
    ];

    /**
     * علاقة طلب الوثيقة بالطالب
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * علاقة الموافق على الطلب
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * الحصول على اسم نوع الوثيقة بالعربية
     */
    public function getDocumentTypeNameAttribute()
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * فلترة الطلبات حسب الحالة
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * فلترة الطلبات حسب نوع الوثيقة
     */
    public function scopeByDocumentType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * فلترة الطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * فلترة الطلبات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
