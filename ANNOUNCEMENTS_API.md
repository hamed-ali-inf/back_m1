# Announcements API Documentation

## نظام الإعلانات

هذا النظام يدير الإعلانات المختلفة في الجامعة مع إمكانية استهداف فئات محددة وإدارة الأولويات والحالات.

## أنواع الأهداف المدعومة

```php
'general' => 'عامة (لجميع الجامعة)'
'institute' => 'حسب المعهد'
'department' => 'حسب القسم'
'level' => 'حسب المستوى'
'group' => 'حسب الفوج'
'course' => 'حسب المادة'
'teacher' => 'حسب الأستاذ'
```

## حالات الإعلان

```php
'draft' => 'مسودة'
'published' => 'منشور'
'archived' => 'مؤرشف'
'expired' => 'منتهي الصلاحية'
```

## أولويات الإعلان

```php
'low' => 'منخفضة'
'normal' => 'عادية'
'high' => 'عالية'
'urgent' => 'عاجلة'
```

## API Endpoints

### 1. عرض جميع الإعلانات
```http
GET /api/announcements
```

**Parameters:**
- `status` (optional): فلترة حسب الحالة
- `target_type` (optional): فلترة حسب نوع الهدف
- `target_id` (optional): فلترة حسب الهدف المحدد
- `priority` (optional): فلترة حسب الأولوية
- `sender_id` (optional): فلترة حسب المرسل
- `active_only` (optional): عرض الإعلانات النشطة فقط
- `student_id` (optional): فلترة للطالب المحدد
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
                "sender_id": 1,
                "title": "إعلان هام: بداية العام الدراسي",
                "content": "نعلن لجميع الطلاب عن بداية العام الدراسي الجديد...",
                "target_type": "general",
                "target_type_name": "عامة (لجميع الجامعة)",
                "target_id": null,
                "status": "published",
                "status_name": "منشور",
                "priority": "high",
                "priority_name": "عالية",
                "published_at": "2025-12-07T23:00:00.000000Z",
                "expires_at": null,
                "is_pinned": true,
                "is_expired": false,
                "is_active": true,
                "attachments": null,
                "views_count": 150,
                "created_at": "2025-12-10T23:00:00.000000Z",
                "updated_at": "2025-12-10T23:00:00.000000Z",
                "sender": {
                    "id": 1,
                    "name": "د. أحمد محمد"
                }
            }
        ],
        "total": 25
    },
    "target_types": {
        "general": "عامة (لجميع الجامعة)",
        "institute": "حسب المعهد"
    },
    "statuses": {
        "draft": "مسودة",
        "published": "منشور"
    },
    "priorities": {
        "low": "منخفضة",
        "normal": "عادية"
    }
}
```

### 2. إنشاء إعلان جديد
```http
POST /api/announcements
```

**Request Body:**
```json
{
    "title": "إعلان هام: ورشة عمل تقنية",
    "content": "نعلن عن إقامة ورشة عمل تقنية متخصصة في البرمجة والذكاء الاصطناعي يوم الخميس القادم في قاعة المؤتمرات الرئيسية.",
    "target_type": "department",
    "target_id": 1,
    "priority": "high",
    "expires_at": "2025-12-20T23:59:59",
    "is_pinned": false,
    "attachments": [
        "ورشة_العمل_التقنية.pdf",
        "جدول_الورشة.xlsx"
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إنشاء الإعلان بنجاح",
    "data": {
        "id": 1,
        "sender_id": 1,
        "title": "إعلان هام: ورشة عمل تقنية",
        "content": "نعلن عن إقامة ورشة عمل تقنية...",
        "target_type": "department",
        "target_id": 1,
        "status": "draft",
        "priority": "high",
        "created_at": "2025-12-10T23:00:00.000000Z"
    }
}
```

### 3. عرض تفاصيل إعلان محدد
```http
GET /api/announcements/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "sender_id": 1,
        "title": "إعلان هام: بداية العام الدراسي",
        "content": "نعلن لجميع الطلاب عن بداية العام الدراسي الجديد...",
        "target_type": "general",
        "target_type_name": "عامة (لجميع الجامعة)",
        "target_id": null,
        "status": "published",
        "status_name": "منشور",
        "priority": "high",
        "priority_name": "عالية",
        "published_at": "2025-12-07T23:00:00.000000Z",
        "expires_at": null,
        "is_pinned": true,
        "is_expired": false,
        "is_active": true,
        "attachments": [
            "دليل_الطالب.pdf",
            "قوانين_الجامعة.pdf"
        ],
        "views_count": 151,
        "sender": {
            "id": 1,
            "name": "د. أحمد محمد",
            "email": "ahmed@university.edu"
        }
    }
}
```

### 4. تحديث الإعلان
```http
PUT /api/announcements/{id}
```

**Request Body:**
```json
{
    "title": "إعلان محدث: ورشة عمل تقنية",
    "content": "تم تحديث تفاصيل ورشة العمل التقنية...",
    "priority": "urgent",
    "expires_at": "2025-12-25T23:59:59"
}
```

### 5. نشر الإعلان
```http
POST /api/announcements/{id}/publish
```

**Response:**
```json
{
    "success": true,
    "message": "تم نشر الإعلان بنجاح",
    "data": {
        "id": 1,
        "status": "published",
        "published_at": "2025-12-10T23:00:00.000000Z"
    }
}
```

### 6. أرشفة الإعلان
```http
POST /api/announcements/{id}/archive
```

**Response:**
```json
{
    "success": true,
    "message": "تم أرشفة الإعلان بنجاح",
    "data": {
        "id": 1,
        "status": "archived"
    }
}
```

### 7. تثبيت/إلغاء تثبيت الإعلان
```http
POST /api/announcements/{id}/toggle-pin
```

**Response:**
```json
{
    "success": true,
    "message": "تم تثبيت الإعلان",
    "data": {
        "id": 1,
        "is_pinned": true
    }
}
```

### 8. حذف الإعلان
```http
DELETE /api/announcements/{id}
```

**Response:**
```json
{
    "success": true,
    "message": "تم حذف الإعلان بنجاح"
}
```

### 9. الإعلانات للطالب المحدد
```http
GET /api/announcements/student/{studentId}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "إعلان عام للجامعة",
                "content": "إعلان يخص جميع الطلاب...",
                "priority": "high",
                "is_pinned": true,
                "published_at": "2025-12-10T23:00:00.000000Z"
            },
            {
                "id": 2,
                "title": "إعلان خاص بالقسم",
                "content": "إعلان يخص طلاب هذا القسم...",
                "priority": "normal",
                "is_pinned": false,
                "published_at": "2025-12-09T23:00:00.000000Z"
            }
        ]
    }
}
```

### 10. الإعلانات العاجلة
```http
GET /api/announcements-urgent
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "إعلان عاجل: تأجيل الامتحانات",
            "content": "نظراً للظروف الاستثنائية...",
            "priority": "urgent",
            "published_at": "2025-12-10T21:00:00.000000Z",
            "sender": {
                "id": 1,
                "name": "إدارة الجامعة"
            }
        }
    ]
}
```

### 11. الإعلانات المثبتة
```http
GET /api/announcements-pinned
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "إعلان مثبت: قوانين الجامعة",
            "content": "قوانين ولوائح الجامعة المحدثة...",
            "is_pinned": true,
            "published_at": "2025-12-01T23:00:00.000000Z"
        }
    ]
}
```

### 12. إحصائيات الإعلانات
```http
GET /api/announcements-statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "general_stats": {
            "total": 50,
            "published": 35,
            "draft": 8,
            "archived": 5,
            "expired": 2,
            "pinned": 3
        },
        "by_priority": {
            "urgent": {
                "name": "عاجلة",
                "count": 5
            },
            "high": {
                "name": "عالية",
                "count": 12
            },
            "normal": {
                "name": "عادية",
                "count": 28
            },
            "low": {
                "name": "منخفضة",
                "count": 5
            }
        },
        "by_target_type": {
            "general": {
                "name": "عامة (لجميع الجامعة)",
                "count": 15
            },
            "department": {
                "name": "حسب القسم",
                "count": 20
            },
            "course": {
                "name": "حسب المادة",
                "count": 10
            },
            "institute": {
                "name": "حسب المعهد",
                "count": 5
            }
        },
        "most_viewed": [
            {
                "id": 1,
                "title": "إعلان بداية العام الدراسي",
                "views_count": 500
            },
            {
                "id": 2,
                "title": "نتائج الامتحانات",
                "views_count": 450
            }
        ]
    }
}
```

### 13. البحث في الإعلانات
```http
GET /api/announcements-search
```

**Parameters:**
- `query` (required): نص البحث
- `target_type` (optional): فلترة حسب نوع الهدف
- `priority` (optional): فلترة حسب الأولوية
- `status` (optional): فلترة حسب الحالة

**Example:**
```http
GET /api/announcements-search?query=امتحان&priority=high&status=published
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "إعلان عاجل: تأجيل الامتحانات",
                "content": "نظراً للظروف الاستثنائية، تم تأجيل جميع الامتحانات...",
                "priority": "urgent",
                "status": "published"
            }
        ]
    }
}
```

### 14. تحديث الإعلانات المنتهية الصلاحية
```http
POST /api/announcements-update-expired
```

**Response:**
```json
{
    "success": true,
    "message": "تم تحديث حالة الإعلانات المنتهية الصلاحية"
}
```

## Validation Rules

### إنشاء إعلان جديد:
- `title`: مطلوب، نص لا يتجاوز 255 حرف
- `content`: مطلوب، نص لا يقل عن 10 أحرف
- `target_type`: مطلوب، يجب أن يكون من الأنواع المحددة
- `target_id`: اختياري، رقم صحيح (مطلوب لبعض أنواع الأهداف)
- `priority`: اختياري، يجب أن يكون من الأولويات المحددة
- `expires_at`: اختياري، تاريخ في المستقبل
- `is_pinned`: اختياري، قيمة منطقية
- `attachments`: اختياري، مصفوفة لا تتجاوز 5 عناصر

### تحديث الإعلان:
- جميع الحقول اختيارية
- نفس قواعد الإنشاء مع إضافة `status`

### البحث:
- `query`: مطلوب، نص لا يقل عن حرفين
- باقي المعايير اختيارية

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

```sql
CREATE TABLE announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    target_type ENUM('general', 'institute', 'department', 'level', 'group', 'course', 'teacher') NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    status ENUM('draft', 'published', 'archived', 'expired') NOT NULL DEFAULT 'draft',
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    published_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    attachments JSON NULL,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_target (target_type, target_id),
    INDEX idx_status_published (status, published_at),
    INDEX idx_priority_created (priority, created_at)
);
```

## Usage Examples

### إنشاء إعلان عام عاجل:
```bash
curl -X POST http://localhost:8000/api/announcements \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "إعلان عاجل: إلغاء المحاضرات",
    "content": "تم إلغاء جميع المحاضرات اليوم بسبب الأحوال الجوية",
    "target_type": "general",
    "priority": "urgent"
  }'
```

### نشر إعلان:
```bash
curl -X POST http://localhost:8000/api/announcements/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### البحث في الإعلانات:
```bash
curl "http://localhost:8000/api/announcements-search?query=امتحان&priority=high"
```

### الحصول على إعلانات طالب محدد:
```bash
curl "http://localhost:8000/api/announcements/student/1?active_only=true"
```

### فلترة الإعلانات حسب القسم:
```bash
curl "http://localhost:8000/api/announcements?target_type=department&target_id=1&status=published"
```

## Features

- **استهداف ذكي**: إمكانية استهداف فئات محددة من الطلاب
- **إدارة الأولويات**: ترتيب الإعلانات حسب الأهمية
- **التثبيت**: إمكانية تثبيت الإعلانات المهمة
- **انتهاء الصلاحية**: إدارة تلقائية للإعلانات المنتهية الصلاحية
- **المرفقات**: إمكانية إرفاق ملفات مع الإعلانات
- **إحصائيات المشاهدة**: تتبع عدد مشاهدات كل إعلان
- **البحث المتقدم**: بحث في العنوان والمحتوى مع فلاتر متعددة
- **إدارة الحالات**: مسودة، منشور، مؤرشف، منتهي الصلاحية