<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'role',
        'joined_at',
        'last_seen_at',
        'last_read_at',
        'is_muted',
        'is_pinned',
        'is_archived',
        'unread_count'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'unread_count' => 'integer'
    ];

    // أدوار المشاركين
    const ROLES = [
        'admin' => 'مدير المحادثة',
        'moderator' => 'مشرف',
        'member' => 'عضو عادي'
    ];

    /**
     * علاقة المحادثة
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * علاقة المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على اسم الدور بالعربية
     */
    public function getRoleNameAttribute()
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * فلترة المشاركين النشطين (غير المؤرشفين)
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * فلترة المشاركين المؤرشفين
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * فلترة المشاركين المثبتين
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * فلترة المشاركين الصامتين
     */
    public function scopeMuted($query)
    {
        return $query->where('is_muted', true);
    }

    /**
     * فلترة حسب الدور
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * فلترة المديرين
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * فلترة المشرفين
     */
    public function scopeModerators($query)
    {
        return $query->where('role', 'moderator');
    }

    /**
     * فلترة الأعضاء العاديين
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    /**
     * تحديث آخر ظهور
     */
    public function updateLastSeen()
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * تحديث آخر قراءة
     */
    public function updateLastRead()
    {
        $this->update([
            'last_read_at' => now(),
            'unread_count' => 0
        ]);
    }

    /**
     * زيادة عدد الرسائل غير المقروءة
     */
    public function incrementUnreadCount()
    {
        $this->increment('unread_count');
    }

    /**
     * تصفير عدد الرسائل غير المقروءة
     */
    public function resetUnreadCount()
    {
        $this->update(['unread_count' => 0]);
    }

    /**
     * كتم/إلغاء كتم الإشعارات
     */
    public function toggleMute()
    {
        $this->update(['is_muted' => !$this->is_muted]);
    }

    /**
     * تثبيت/إلغاء تثبيت المحادثة
     */
    public function togglePin()
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    /**
     * أرشفة/إلغاء أرشفة المحادثة
     */
    public function toggleArchive()
    {
        $this->update(['is_archived' => !$this->is_archived]);
    }

    /**
     * تغيير دور المشارك
     */
    public function changeRole($newRole)
    {
        if (in_array($newRole, array_keys(self::ROLES))) {
            $this->update(['role' => $newRole]);
        }
    }

    /**
     * التحقق من صلاحيات المشارك
     */
    public function canManageChat()
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    /**
     * التحقق من كون المشارك مدير
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * التحقق من كون المشارك مشرف
     */
    public function isModerator()
    {
        return $this->role === 'moderator';
    }

    /**
     * التحقق من كون المشارك عضو عادي
     */
    public function isMember()
    {
        return $this->role === 'member';
    }
}
