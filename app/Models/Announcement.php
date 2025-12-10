<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'title',
        'content',
        'target_type',
        'target_id',
        'status',
        'priority',
        'published_at',
        'expires_at',
        'is_pinned',
        'attachments',
        'views_count'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean',
        'attachments' => 'array',
        'views_count' => 'integer'
    ];

    // أنواع الأهداف
    const TARGET_TYPES = [
        'general' => 'عامة (لجميع الجامعة)',
        'institute' => 'حسب المعهد',
        'department' => 'حسب القسم',
        'level' => 'حسب المستوى',
        'group' => 'حسب الفوج',
        'course' => 'حسب المادة',
        'teacher' => 'حسب الأستاذ'
    ];

    // حالات الإعلان
    const STATUSES = [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
        'expired' => 'منتهي الصلاحية'
    ];

    // أولويات الإعلان
    const PRIORITIES = [
        'low' => 'منخفضة',
        'normal' => 'عادية',
        'high' => 'عالية',
        'urgent' => 'عاجلة'
    ];

    /**
     * علاقة المرسل
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * علاقة الهدف (polymorphic)
     */
    public function target()
    {
        switch ($this->target_type) {
            case 'institute':
                return $this->belongsTo(Institute::class, 'target_id');
            case 'department':
                return $this->belongsTo(Department::class, 'target_id');
            case 'course':
                return $this->belongsTo(Course::class, 'target_id');
            case 'teacher':
                return $this->belongsTo(Teacher::class, 'target_id');
            default:
                return null;
        }
    }

    /**
     * الحصول على اسم نوع الهدف بالعربية
     */
    public function getTargetTypeNameAttribute()
    {
        return self::TARGET_TYPES[$this->target_type] ?? $this->target_type;
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * الحصول على اسم الأولوية بالعربية
     */
    public function getPriorityNameAttribute()
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * التحقق من انتهاء صلاحية الإعلان
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * التحقق من كون الإعلان منشور وساري
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'published' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * فلترة الإعلانات المنشورة
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * فلترة الإعلانات النشطة (منشورة وغير منتهية الصلاحية)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * فلترة الإعلانات المثبتة
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * فلترة حسب نوع الهدف
     */
    public function scopeByTargetType($query, $type)
    {
        return $query->where('target_type', $type);
    }

    /**
     * فلترة حسب الهدف المحدد
     */
    public function scopeByTarget($query, $type, $id = null)
    {
        $query->where('target_type', $type);
        
        if ($id !== null) {
            $query->where('target_id', $id);
        }
        
        return $query;
    }

    /**
     * فلترة حسب الأولوية
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * ترتيب حسب الأولوية والتاريخ
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
            END
        ")->orderBy('is_pinned', 'desc')
          ->orderBy('published_at', 'desc');
    }

    /**
     * فلترة الإعلانات للطالب المحدد
     */
    public function scopeForStudent($query, $studentId)
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return $query->whereRaw('1 = 0'); // لا توجد نتائج
        }

        return $query->where(function ($q) use ($student) {
            // الإعلانات العامة
            $q->where('target_type', 'general')
              // إعلانات المعهد
              ->orWhere(function ($subQ) use ($student) {
                  $subQ->where('target_type', 'institute')
                       ->where('target_id', $student->department->institute_id ?? null);
              })
              // إعلانات القسم
              ->orWhere(function ($subQ) use ($student) {
                  $subQ->where('target_type', 'department')
                       ->where('target_id', $student->department_id);
              })
              // إعلانات المستوى
              ->orWhere(function ($subQ) use ($student) {
                  $subQ->where('target_type', 'level')
                       ->where('target_id', $student->level);
              });
        });
    }

    /**
     * زيادة عدد المشاهدات
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * نشر الإعلان
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now()
        ]);
    }

    /**
     * أرشفة الإعلان
     */
    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * تحديث حالة الإعلانات المنتهية الصلاحية
     */
    public static function updateExpiredAnnouncements()
    {
        self::where('status', 'published')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
