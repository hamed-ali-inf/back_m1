<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'created_by',
        'context_id',
        'context_type',
        'is_group',
        'is_active',
        'last_message_at',
        'messages_count',
        'settings'
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
        'messages_count' => 'integer',
        'settings' => 'array'
    ];

    // أنواع المحادثات
    const TYPES = [
        'student_teacher' => 'طالب ↔ أستاذ (في مادة محددة)',
        'student_department' => 'طالب ↔ رئيس قسم',
        'student_institute' => 'طالب ↔ رئيس معهد',
        'teacher_teacher' => 'أستاذ ↔ أستاذ (داخل نفس القسم)',
        'group_class' => 'مجموعة (لفصل معين)',
        'group_department' => 'مجموعة (قسم)',
        'group_institute' => 'مجموعة (معهد)',
        'private' => 'محادثة خاصة عامة'
    ];

    /**
     * علاقة منشئ المحادثة
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * علاقة المشاركين في المحادثة
     */
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class);
    }

    /**
     * علاقة المستخدمين المشاركين
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_participants')
                    ->withPivot(['role', 'joined_at', 'last_seen_at', 'last_read_at', 'is_muted', 'is_pinned', 'is_archived', 'unread_count'])
                    ->withTimestamps();
    }

    /**
     * علاقة الرسائل
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * آخر رسالة في المحادثة
     */
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)->latest();
    }

    /**
     * علاقة السياق (polymorphic)
     */
    public function context()
    {
        return $this->morphTo();
    }

    /**
     * الحصول على اسم نوع المحادثة بالعربية
     */
    public function getTypeNameAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * فلترة المحادثات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فلترة المحادثات الجماعية
     */
    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    /**
     * فلترة المحادثات الخاصة
     */
    public function scopePrivate($query)
    {
        return $query->where('is_group', false);
    }

    /**
     * فلترة حسب نوع المحادثة
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * فلترة المحادثات للمستخدم المحدد
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * فلترة المحادثات حسب السياق
     */
    public function scopeByContext($query, $contextType, $contextId = null)
    {
        $query->where('context_type', $contextType);
        
        if ($contextId !== null) {
            $query->where('context_id', $contextId);
        }
        
        return $query;
    }

    /**
     * البحث في المحادثات
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', '%' . $searchTerm . '%')
              ->orWhereHas('messages', function ($msgQuery) use ($searchTerm) {
                  $msgQuery->where('content', 'like', '%' . $searchTerm . '%');
              });
        });
    }

    /**
     * إضافة مشارك جديد للمحادثة
     */
    public function addParticipant($userId, $role = 'member')
    {
        return $this->participants()->firstOrCreate(
            ['user_id' => $userId],
            ['role' => $role, 'joined_at' => now()]
        );
    }

    /**
     * إزالة مشارك من المحادثة
     */
    public function removeParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->delete();
    }

    /**
     * التحقق من كون المستخدم مشارك في المحادثة
     */
    public function hasParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * الحصول على دور المستخدم في المحادثة
     */
    public function getUserRole($userId)
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        return $participant ? $participant->role : null;
    }

    /**
     * تحديث آخر رسالة
     */
    public function updateLastMessage()
    {
        $lastMessage = $this->messages()->latest()->first();
        
        $this->update([
            'last_message_at' => $lastMessage ? $lastMessage->created_at : null,
            'messages_count' => $this->messages()->count()
        ]);
    }

    /**
     * إنشاء محادثة بين طالب وأستاذ
     */
    public static function createStudentTeacherChat($studentId, $teacherId, $courseId)
    {
        $chat = self::create([
            'type' => 'student_teacher',
            'created_by' => $studentId,
            'context_id' => $courseId,
            'context_type' => 'course',
            'is_group' => false
        ]);

        $chat->addParticipant($studentId, 'member');
        $chat->addParticipant($teacherId, 'admin');

        return $chat;
    }

    /**
     * إنشاء محادثة جماعية
     */
    public static function createGroupChat($title, $type, $createdBy, $contextId = null, $contextType = null)
    {
        return self::create([
            'title' => $title,
            'type' => $type,
            'created_by' => $createdBy,
            'context_id' => $contextId,
            'context_type' => $contextType,
            'is_group' => true
        ]);
    }

    /**
     * البحث عن محادثة موجودة أو إنشاء جديدة
     */
    public static function findOrCreatePrivateChat($user1Id, $user2Id, $type = 'private', $contextId = null, $contextType = null)
    {
        // البحث عن محادثة موجودة
        $chat = self::where('type', $type)
                    ->where('is_group', false)
                    ->where('context_id', $contextId)
                    ->where('context_type', $contextType)
                    ->whereHas('participants', function ($q) use ($user1Id) {
                        $q->where('user_id', $user1Id);
                    })
                    ->whereHas('participants', function ($q) use ($user2Id) {
                        $q->where('user_id', $user2Id);
                    })
                    ->first();

        if (!$chat) {
            // إنشاء محادثة جديدة
            $chat = self::create([
                'type' => $type,
                'created_by' => $user1Id,
                'context_id' => $contextId,
                'context_type' => $contextType,
                'is_group' => false
            ]);

            $chat->addParticipant($user1Id, 'member');
            $chat->addParticipant($user2Id, 'member');
        }

        return $chat;
    }
}
