# Chat System API Documentation

## نظام المحادثات

هذا النظام يدير جميع أنواع المحادثات في الجامعة مع دعم المراسلة المباشرة والمحادثات الجماعية.

## أنواع المحادثات المدعومة

```php
'student_teacher' => 'طالب ↔ أستاذ (في مادة محددة)'
'student_department' => 'طالب ↔ رئيس قسم'
'student_institute' => 'طالب ↔ رئيس معهد'
'teacher_teacher' => 'أستاذ ↔ أستاذ (داخل نفس القسم)'
'group_class' => 'مجموعة (لفصل معين)'
'group_department' => 'مجموعة (قسم)'
'group_institute' => 'مجموعة (معهد)'
'private' => 'محادثة خاصة عامة'
```

## أنواع الرسائل

```php
'text' => 'رسالة نصية'
'file' => 'ملف'
'image' => 'صورة'
'voice' => 'رسالة صوتية'
'system' => 'رسالة نظام'
```

## أدوار المشاركين

```php
'admin' => 'مدير المحادثة'
'moderator' => 'مشرف'
'member' => 'عضو عادي'
```

## Chat API Endpoints

### 1. عرض جميع المحادثات للمستخدم الحالي
```http
GET /api/chats
```

**Parameters:**
- `type` (optional): فلترة حسب نوع المحادثة
- `is_group` (optional): فلترة المحادثات الجماعية أو الخاصة
- `active_only` (optional): عرض المحادثات النشطة فقط
- `search` (optional): البحث في المحادثات
- `page` (optional): رقم الصفحة للتصفح

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "مجموعة قسم علوم الحاسوب",
                "type": "group_department",
                "type_name": "مجموعة (قسم)",
                "created_by": 1,
                "context_id": 1,
                "context_type": "department",
                "is_group": true,
                "is_active": true,
                "last_message_at": "2025-12-10T20:30:00.000000Z",
                "messages_count": 25,
                "current_user_participant": {
                    "role": "member",
                    "unread_count": 3,
                    "is_muted": false,
                    "is_pinned": true,
                    "is_archived": false
                },
                "last_message": {
                    "id": 50,
                    "content": "شكراً للمشاركة",
                    "type": "text",
                    "created_at": "2025-12-10T20:30:00.000000Z",
                    "sender": {
                        "id": 2,
                        "name": "أحمد محمد"
                    }
                },
                "participants": [
                    {
                        "id": 1,
                        "user_id": 1,
                        "role": "admin",
                        "user": {
                            "id": 1,
                            "name": "د. محمد علي"
                        }
                    }
                ]
            }
        ],
        "total": 15
    },
    "chat_types": {
        "student_teacher": "طالب ↔ أستاذ (في مادة محددة)",
        "group_department": "مجموعة (قسم)"
    }
}
```

### 2. إنشاء محادثة جديدة
```http
POST /api/chats
```

**Request Body:**
```json
{
    "type": "group_department",
    "title": "مجموعة مناقشة المشاريع",
    "participants": [2, 3, 4, 5],
    "context_id": 1,
    "context_type": "department",
    "is_group": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إنشاء المحادثة بنجاح",
    "data": {
        "id": 1,
        "title": "مجموعة مناقشة المشاريع",
        "type": "group_department",
        "created_by": 1,
        "is_group": true,
        "participants": [
            {
                "user_id": 1,
                "role": "admin",
                "user": {
                    "id": 1,
                    "name": "د. محمد علي"
                }
            }
        ]
    }
}
```

### 3. عرض تفاصيل محادثة محددة
```http
GET /api/chats/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "مجموعة قسم علوم الحاسوب",
        "type": "group_department",
        "type_name": "مجموعة (قسم)",
        "is_group": true,
        "is_active": true,
        "messages_count": 25,
        "participants": [
            {
                "user_id": 1,
                "role": "admin",
                "joined_at": "2025-12-01T10:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "د. محمد علي",
                    "email": "mohamed@university.edu"
                }
            }
        ],
        "messages": [
            {
                "id": 1,
                "content": "مرحباً بكم في المجموعة",
                "type": "text",
                "type_name": "رسالة نصية",
                "is_edited": false,
                "is_deleted": false,
                "read_count": 5,
                "created_at": "2025-12-10T20:00:00.000000Z",
                "sender": {
                    "id": 1,
                    "name": "د. محمد علي"
                },
                "reply_to": null
            }
        ]
    }
}
```

### 4. تحديث معلومات المحادثة
```http
PUT /api/chats/{id}
```

**Request Body:**
```json
{
    "title": "مجموعة قسم علوم الحاسوب - محدثة",
    "is_active": true,
    "settings": {
        "allow_file_sharing": true,
        "max_file_size": 10485760
    }
}
```

### 5. حذف المحادثة
```http
DELETE /api/chats/{id}
```

**Response:**
```json
{
    "success": true,
    "message": "تم حذف المحادثة بنجاح"
}
```

### 6. إرسال رسالة في المحادثة
```http
POST /api/chats/{id}/send-message
```

**Request Body:**
```json
{
    "content": "مرحباً، كيف يمكنني المساعدة؟",
    "reply_to_id": 15
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إرسال الرسالة بنجاح",
    "data": {
        "id": 51,
        "chat_id": 1,
        "content": "مرحباً، كيف يمكنني المساعدة؟",
        "type": "text",
        "reply_to_id": 15,
        "created_at": "2025-12-10T21:00:00.000000Z",
        "sender": {
            "id": 1,
            "name": "د. محمد علي"
        },
        "reply_to": {
            "id": 15,
            "content": "لدي سؤال حول المشروع",
            "sender": {
                "id": 2,
                "name": "أحمد محمد"
            }
        }
    }
}
```

### 7. رفع ملف في المحادثة
```http
POST /api/chats/{id}/upload-file
```

**Request Body (multipart/form-data):**
- `file`: الملف المراد رفعه (حد أقصى 10MB)
- `caption`: وصف اختياري للملف

**Response:**
```json
{
    "success": true,
    "message": "تم رفع الملف بنجاح",
    "data": {
        "id": 52,
        "chat_id": 1,
        "content": "تقرير المشروع النهائي.pdf",
        "type": "file",
        "file_name": "تقرير المشروع النهائي.pdf",
        "file_path": "chat_files/abc123.pdf",
        "file_type": "application/pdf",
        "file_size": 2048576,
        "file_url": "http://localhost:8000/storage/chat_files/abc123.pdf",
        "formatted_file_size": "2.00 MB",
        "created_at": "2025-12-10T21:05:00.000000Z",
        "sender": {
            "id": 1,
            "name": "د. محمد علي"
        }
    }
}
```

### 8. إضافة مشارك جديد للمحادثة
```http
POST /api/chats/{id}/add-participant
```

**Request Body:**
```json
{
    "user_id": 6,
    "role": "member"
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إضافة المشارك بنجاح",
    "data": {
        "id": 10,
        "chat_id": 1,
        "user_id": 6,
        "role": "member",
        "joined_at": "2025-12-10T21:10:00.000000Z",
        "user": {
            "id": 6,
            "name": "سارة أحمد",
            "email": "sara@university.edu"
        }
    }
}
```

### 9. إزالة مشارك من المحادثة
```http
DELETE /api/chats/{id}/remove-participant
```

**Request Body:**
```json
{
    "user_id": 6
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إزالة المشارك بنجاح"
}
```

### 10. تحديد الرسائل كمقروءة
```http
POST /api/chats/{id}/mark-as-read
```

**Response:**
```json
{
    "success": true,
    "message": "تم تحديد الرسائل كمقروءة"
}
```

### 11. البحث في رسائل المحادثة
```http
GET /api/chats/{id}/search-messages
```

**Parameters:**
- `query` (required): نص البحث
- `type` (optional): نوع الرسالة

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 25,
                "content": "البحث عن المعلومات في قاعدة البيانات",
                "type": "text",
                "created_at": "2025-12-09T15:30:00.000000Z",
                "sender": {
                    "id": 2,
                    "name": "أحمد محمد"
                }
            }
        ]
    }
}
```

### 12. إنشاء محادثة بين طالب وأستاذ
```http
POST /api/chats/create-student-teacher
```

**Request Body:**
```json
{
    "teacher_id": 5,
    "course_id": 3
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إنشاء المحادثة بنجاح",
    "data": {
        "id": 15,
        "type": "student_teacher",
        "context_id": 3,
        "context_type": "course",
        "participants": [
            {
                "user_id": 1,
                "role": "member"
            },
            {
                "user_id": 5,
                "role": "admin"
            }
        ]
    }
}
```

### 13. إحصائيات المحادثات
```http
GET /api/chats-statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "general_stats": {
            "total_chats": 25,
            "active_chats": 22,
            "group_chats": 8,
            "private_chats": 17,
            "unread_messages": 12,
            "pinned_chats": 3,
            "archived_chats": 1
        },
        "by_type": {
            "student_teacher": {
                "name": "طالب ↔ أستاذ (في مادة محددة)",
                "count": 10
            },
            "group_department": {
                "name": "مجموعة (قسم)",
                "count": 5
            }
        }
    }
}
```

## Chat Messages API Endpoints

### 1. عرض رسائل المحادثة
```http
GET /api/chats/{chatId}/messages
```

**Parameters:**
- `type` (optional): فلترة حسب نوع الرسالة
- `files_only` (optional): عرض الملفات فقط
- `search` (optional): البحث في المحتوى
- `sender_id` (optional): فلترة حسب المرسل
- `page` (optional): رقم الصفحة

### 2. عرض تفاصيل رسالة محددة
```http
GET /api/chat-messages/{id}
```

### 3. تعديل رسالة
```http
PUT /api/chat-messages/{id}
```

**Request Body:**
```json
{
    "content": "المحتوى المحدث للرسالة"
}
```

### 4. حذف رسالة
```http
DELETE /api/chat-messages/{id}
```

### 5. الرد على رسالة
```http
POST /api/chat-messages/{id}/reply
```

**Request Body:**
```json
{
    "content": "هذا رد على الرسالة"
}
```

### 6. تحديد رسالة كمقروءة
```http
POST /api/chat-messages/{id}/mark-as-read
```

### 7. الحصول على الردود على رسالة
```http
GET /api/chat-messages/{id}/replies
```

### 8. تحميل ملف من رسالة
```http
GET /api/chat-messages/{id}/download
```

### 9. البحث العام في الرسائل
```http
GET /api/chat-messages-search
```

**Parameters:**
- `query` (required): نص البحث
- `type` (optional): نوع الرسالة
- `chat_type` (optional): نوع المحادثة

### 10. إحصائيات رسائل المحادثة
```http
GET /api/chats/{chatId}/messages-statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "general_stats": {
            "total_messages": 150,
            "text_messages": 120,
            "file_messages": 25,
            "system_messages": 5,
            "my_messages": 45
        },
        "by_type": {
            "text": {
                "name": "رسالة نصية",
                "count": 120
            },
            "file": {
                "name": "ملف",
                "count": 20
            },
            "image": {
                "name": "صورة",
                "count": 5
            }
        },
        "most_active_users": [
            {
                "sender_id": 1,
                "message_count": 45,
                "sender": {
                    "id": 1,
                    "name": "د. محمد علي"
                }
            }
        ]
    }
}
```

## Validation Rules

### إنشاء محادثة:
- `type`: مطلوب، يجب أن يكون من الأنواع المحددة
- `title`: اختياري للمحادثات الجماعية، نص لا يتجاوز 255 حرف
- `participants`: مطلوب، مصفوفة من معرفات المستخدمين
- `context_id`: اختياري، رقم صحيح
- `context_type`: اختياري، نص (course, department, institute)
- `is_group`: اختياري، قيمة منطقية

### إرسال رسالة:
- `content`: مطلوب، نص لا يتجاوز 5000 حرف
- `reply_to_id`: اختياري، معرف رسالة موجودة

### رفع ملف:
- `file`: مطلوب، ملف بحد أقصى 10MB
- `caption`: اختياري، نص لا يتجاوز 1000 حرف

### إضافة مشارك:
- `user_id`: مطلوب، معرف مستخدم موجود
- `role`: اختياري، يجب أن يكون من الأدوار المحددة

## Error Responses

```json
{
    "success": false,
    "message": "رسالة الخطأ",
    "errors": {
        "field_name": ["رسالة الخطأ التفصيلية"]
    }
}
```

## Database Schema

### جدول المحادثات (chats)
```sql
CREATE TABLE chats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NULL,
    type ENUM('student_teacher', 'student_department', 'student_institute', 'teacher_teacher', 'group_class', 'group_department', 'group_institute', 'private') NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    context_id BIGINT UNSIGNED NULL,
    context_type VARCHAR(255) NULL,
    is_group BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_message_at TIMESTAMP NULL,
    messages_count INT UNSIGNED NOT NULL DEFAULT 0,
    settings JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type_active (type, is_active),
    INDEX idx_context (context_type, context_id),
    INDEX idx_creator (created_by, created_at),
    INDEX idx_last_message (last_message_at)
);
```

### جدول المشاركين (chat_participants)
```sql
CREATE TABLE chat_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('admin', 'moderator', 'member') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    last_read_at TIMESTAMP NULL,
    is_muted BOOLEAN NOT NULL DEFAULT FALSE,
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    is_archived BOOLEAN NOT NULL DEFAULT FALSE,
    unread_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat_user (chat_id, user_id),
    INDEX idx_user_archived (user_id, is_archived),
    INDEX idx_chat_role (chat_id, role),
    INDEX idx_last_seen (last_seen_at)
);
```

### جدول الرسائل (chat_messages)
```sql
CREATE TABLE chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    type ENUM('text', 'file', 'image', 'voice', 'system') NOT NULL DEFAULT 'text',
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(255) NULL,
    file_type VARCHAR(255) NULL,
    file_size BIGINT UNSIGNED NULL,
    reply_to_id BIGINT UNSIGNED NULL,
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    read_count INT UNSIGNED NOT NULL DEFAULT 0,
    read_by JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    INDEX idx_chat_created (chat_id, created_at),
    INDEX idx_sender_created (sender_id, created_at),
    INDEX idx_type_created (type, created_at),
    INDEX idx_reply_to (reply_to_id),
    FULLTEXT idx_content (content)
);
```

## Usage Examples

### إنشاء محادثة جماعية:
```bash
curl -X POST http://localhost:8000/api/chats \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "type": "group_department",
    "title": "مجموعة مناقشة المشاريع",
    "participants": [2, 3, 4, 5],
    "context_id": 1,
    "context_type": "department",
    "is_group": true
  }'
```

### إرسال رسالة:
```bash
curl -X POST http://localhost:8000/api/chats/1/send-message \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "content": "مرحباً بكم في المجموعة"
  }'
```

### البحث في المحادثات:
```bash
curl "http://localhost:8000/api/chats?search=مشروع&type=group_department"
```

### رفع ملف:
```bash
curl -X POST http://localhost:8000/api/chats/1/upload-file \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@document.pdf" \
  -F "caption=تقرير المشروع النهائي"
```

## Features

- **أنواع محادثات متعددة**: دعم جميع أنواع المحادثات المطلوبة في البيئة الجامعية
- **مراسلة مباشرة**: إرسال واستقبال الرسائل في الوقت الفعلي
- **مشاركة الملفات**: رفع وتحميل الملفات مع دعم أنواع مختلفة
- **المحادثات الجماعية**: إنشاء وإدارة المجموعات مع أدوار مختلفة
- **البحث المتقدم**: البحث في المحادثات والرسائل
- **إدارة الصلاحيات**: أدوار مختلفة للمشاركين (مدير، مشرف، عضو)
- **تتبع القراءة**: معرفة من قرأ الرسائل ومتى
- **الردود**: إمكانية الرد على رسائل محددة
- **تعديل وحذف**: تعديل وحذف الرسائل مع تتبع التغييرات
- **الإشعارات**: عدد الرسائل غير المقروءة وآخر ظهور
- **الأرشفة والتثبيت**: إدارة المحادثات المهمة
- **إحصائيات شاملة**: تقارير مفصلة عن النشاط والاستخدام