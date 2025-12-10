# Document Requests API Documentation

## نظام طلبات الوثائق

هذا النظام يدير طلبات الوثائق المختلفة للطلاب مع إمكانية تتبع حالة كل طلب.

## أنواع الوثائق المدعومة

```php
'registration_certificate' => 'شهادة التسجيل'
'transcript' => 'بيان الدرجات (كشف النقاط)'
'graduation_certificate' => 'شهادة التخرج'
'study_certificate' => 'إفادة دراسة'
'other_administrative' => 'وثائق إدارية أخرى'
```

## حالات الطلب

```php
'pending' => 'قيد الانتظار'
'approved' => 'موافق عليه'
'rejected' => 'مرفوض'
'completed' => 'مكتمل (تم تسليمه)'
'cancelled' => 'ملغي'
```

## API Endpoints

### 1. عرض جميع طلبات الوثائق
```http
GET /api/document-requests
```

**Parameters:**
- `status` (optional): فلترة حسب الحالة
- `document_type` (optional): فلترة حسب نوع الوثيقة
- `student_id` (optional): فلترة حسب الطالب
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
                "student_id": 1,
                "document_type": "registration_certificate",
                "document_type_name": "شهادة التسجيل",
                "status": "pending",
                "status_name": "قيد الانتظار",
                "notes": "طلب عاجل",
                "rejection_reason": null,
                "approved_at": null,
                "completed_at": null,
                "approved_by": null,
                "created_at": "2025-12-10T23:00:00.000000Z",
                "updated_at": "2025-12-10T23:00:00.000000Z",
                "student": {
                    "id": 1,
                    "name": "أحمد محمد"
                },
                "approved_by": null
            }
        ],
        "total": 50
    },
    "document_types": {
        "registration_certificate": "شهادة التسجيل",
        "transcript": "بيان الدرجات (كشف النقاط)"
    },
    "statuses": {
        "pending": "قيد الانتظار",
        "approved": "موافق عليه"
    }
}
```

### 2. إنشاء طلب وثيقة جديد
```http
POST /api/document-requests
```

**Request Body:**
```json
{
    "student_id": 1,
    "document_type": "registration_certificate",
    "notes": "طلب عاجل للتقديم في منحة دراسية"
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إنشاء طلب الوثيقة بنجاح",
    "data": {
        "id": 1,
        "student_id": 1,
        "document_type": "registration_certificate",
        "status": "pending",
        "notes": "طلب عاجل للتقديم في منحة دراسية",
        "created_at": "2025-12-10T23:00:00.000000Z"
    }
}
```

### 3. عرض تفاصيل طلب وثيقة محدد
```http
GET /api/document-requests/{id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "student_id": 1,
        "document_type": "registration_certificate",
        "document_type_name": "شهادة التسجيل",
        "status": "approved",
        "status_name": "موافق عليه",
        "notes": "طلب عاجل",
        "rejection_reason": null,
        "approved_at": "2025-12-10T22:00:00.000000Z",
        "completed_at": null,
        "student": {
            "id": 1,
            "name": "أحمد محمد",
            "email": "ahmed@example.com"
        },
        "approved_by": {
            "id": 2,
            "name": "د. محمد علي"
        }
    }
}
```

### 4. تحديث طلب الوثيقة
```http
PUT /api/document-requests/{id}
```

**Request Body:**
```json
{
    "document_type": "transcript",
    "status": "approved",
    "notes": "تم تحديث الطلب"
}
```

### 5. الموافقة على طلب الوثيقة
```http
POST /api/document-requests/{id}/approve
```

**Response:**
```json
{
    "success": true,
    "message": "تم الموافقة على طلب الوثيقة بنجاح",
    "data": {
        "id": 1,
        "status": "approved",
        "approved_at": "2025-12-10T23:00:00.000000Z",
        "approved_by": 2
    }
}
```

### 6. رفض طلب الوثيقة
```http
POST /api/document-requests/{id}/reject
```

**Request Body:**
```json
{
    "rejection_reason": "الطالب لم يكمل المتطلبات الأكاديمية المطلوبة"
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم رفض طلب الوثيقة",
    "data": {
        "id": 1,
        "status": "rejected",
        "rejection_reason": "الطالب لم يكمل المتطلبات الأكاديمية المطلوبة"
    }
}
```

### 7. تسليم الوثيقة (إكمال الطلب)
```http
POST /api/document-requests/{id}/complete
```

**Response:**
```json
{
    "success": true,
    "message": "تم تسليم الوثيقة بنجاح",
    "data": {
        "id": 1,
        "status": "completed",
        "completed_at": "2025-12-10T23:00:00.000000Z"
    }
}
```

### 8. إلغاء طلب الوثيقة
```http
POST /api/document-requests/{id}/cancel
```

**Response:**
```json
{
    "success": true,
    "message": "تم إلغاء طلب الوثيقة",
    "data": {
        "id": 1,
        "status": "cancelled"
    }
}
```

### 9. حذف طلب الوثيقة
```http
DELETE /api/document-requests/{id}
```

**Response:**
```json
{
    "success": true,
    "message": "تم حذف طلب الوثيقة بنجاح"
}
```

### 10. إحصائيات طلبات الوثائق
```http
GET /api/document-requests-statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "general_stats": {
            "total": 150,
            "pending": 25,
            "approved": 30,
            "completed": 80,
            "rejected": 10,
            "cancelled": 5
        },
        "by_document_type": {
            "registration_certificate": {
                "name": "شهادة التسجيل",
                "count": 45
            },
            "transcript": {
                "name": "بيان الدرجات (كشف النقاط)",
                "count": 60
            },
            "graduation_certificate": {
                "name": "شهادة التخرج",
                "count": 25
            },
            "study_certificate": {
                "name": "إفادة دراسة",
                "count": 15
            },
            "other_administrative": {
                "name": "وثائق إدارية أخرى",
                "count": 5
            }
        }
    }
}
```

## Validation Rules

### إنشاء طلب جديد:
- `student_id`: مطلوب، يجب أن يكون موجود في جدول الطلاب
- `document_type`: مطلوب، يجب أن يكون من الأنواع المحددة
- `notes`: اختياري، نص لا يتجاوز 1000 حرف

### تحديث الطلب:
- `document_type`: اختياري، يجب أن يكون من الأنواع المحددة
- `status`: اختياري، يجب أن يكون من الحالات المحددة
- `notes`: اختياري، نص لا يتجاوز 1000 حرف
- `rejection_reason`: اختياري، نص لا يتجاوز 500 حرف

### رفض الطلب:
- `rejection_reason`: مطلوب، نص لا يتجاوز 500 حرف

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
CREATE TABLE document_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('registration_certificate', 'transcript', 'graduation_certificate', 'study_certificate', 'other_administrative') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    rejection_reason TEXT NULL,
    approved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

## Usage Examples

### إنشاء طلب شهادة تسجيل:
```bash
curl -X POST http://localhost:8000/api/document-requests \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "document_type": "registration_certificate",
    "notes": "طلب عاجل للتقديم في منحة دراسية"
  }'
```

### الموافقة على طلب:
```bash
curl -X POST http://localhost:8000/api/document-requests/1/approve \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### فلترة الطلبات المعلقة:
```bash
curl "http://localhost:8000/api/document-requests?status=pending"
```

### فلترة طلبات طالب محدد:
```bash
curl "http://localhost:8000/api/document-requests?student_id=1"
```