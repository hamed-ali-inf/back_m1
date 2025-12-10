<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'content',
        'type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'reply_to_id',
        'is_edited',
        'is_deleted',
        'edited_at',
        'deleted_at',
        'read_count',
        'read_by'
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'read_count' => 'integer',
        'file_size' => 'integer',
        'read_by' => 'array'
    ];

    // أنواع الرسائل
    const TYPES = [
        'text' => 'رسالة نصية',
        'file' => 'ملف',
        'image' => 'صورة',
        'voice' => 'رسالة صوتية',
        'system' => 'رسالة نظام'
    ];

    /**
     * علاقة المحادثة
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * علاقة المرسل
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * علاقة الرسالة المرد عليها
     */
    public function replyTo()
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }

    /**
     * علاقة الردود على هذه الرسالة
     */
    public function replies()
    {
        return $this->hasMany(ChatMessage::class, 'reply_to_id');
    }

    /**
     * الحصول على اسم نوع الرسالة بالعربية
     */
    public function getTypeNameAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * التحقق من كون الرسالة ملف
     */
    public function getIsFileAttribute()
    {
        return in_array($this->type, ['file', 'image', 'voice']);
    }

    /**
     * الحصول على رابط الملف
     */
    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * الحصول على حجم الملف المنسق
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return null;

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * فلترة الرسائل غير المحذوفة
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * فلترة حسب نوع الرسالة
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * فلترة الرسائل النصية
     */
    public function scopeTextMessages($query)
    {
        return $query->where('type', 'text');
    }

    /**
     * فلترة الملفات
     */
    public function scopeFiles($query)
    {
        return $query->whereIn('type', ['file', 'image', 'voice']);
    }

    /**
     * البحث في محتوى الرسائل
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('content', 'like', '%' . $searchTerm . '%');
    }

    /**
     * فلترة الرسائل المرسلة من مستخدم محدد
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('sender_id', $userId);
    }

    /**
     * تحديد الرسالة كمقروءة من قبل مستخدم
     */
    public function markAsReadBy($userId)
    {
        $readBy = $this->read_by ?? [];
        
        if (!in_array($userId, $readBy)) {
            $readBy[] = $userId;
            $this->update([
                'read_by' => $readBy,
                'read_count' => count($readBy)
            ]);
        }
    }

    /**
     * التحقق من قراءة الرسالة من قبل مستخدم
     */
    public function isReadBy($userId)
    {
        return in_array($userId, $this->read_by ?? []);
    }

    /**
     * تعديل الرسالة
     */
    public function editContent($newContent)
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now()
        ]);
    }

    /**
     * حذف الرسالة (حذف ناعم)
     */
    public function softDelete()
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'content' => 'تم حذف هذه الرسالة'
        ]);
    }

    /**
     * استعادة الرسالة المحذوفة
     */
    public function restore($originalContent)
    {
        $this->update([
            'is_deleted' => false,
            'deleted_at' => null,
            'content' => $originalContent
        ]);
    }

    /**
     * إنشاء رسالة نصية
     */
    public static function createTextMessage($chatId, $senderId, $content, $replyToId = null)
    {
        $message = self::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'content' => $content,
            'type' => 'text',
            'reply_to_id' => $replyToId
        ]);

        // تحديث آخر رسالة في المحادثة
        $message->chat->updateLastMessage();

        return $message;
    }

    /**
     * إنشاء رسالة ملف
     */
    public static function createFileMessage($chatId, $senderId, $fileName, $filePath, $fileType, $fileSize, $content = null)
    {
        $type = 'file';
        
        // تحديد نوع الرسالة حسب نوع الملف
        if (str_starts_with($fileType, 'image/')) {
            $type = 'image';
        } elseif (str_starts_with($fileType, 'audio/')) {
            $type = 'voice';
        }

        $message = self::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'content' => $content ?? $fileName,
            'type' => $type,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize
        ]);

        // تحديث آخر رسالة في المحادثة
        $message->chat->updateLastMessage();

        return $message;
    }

    /**
     * إنشاء رسالة نظام
     */
    public static function createSystemMessage($chatId, $content)
    {
        $message = self::create([
            'chat_id' => $chatId,
            'sender_id' => null, // رسائل النظام ليس لها مرسل
            'content' => $content,
            'type' => 'system'
        ]);

        // تحديث آخر رسالة في المحادثة
        $message->chat->updateLastMessage();

        return $message;
    }
}
