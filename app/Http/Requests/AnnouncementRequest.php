<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Announcement;

class AnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'target_type' => ['required', Rule::in(array_keys(Announcement::TARGET_TYPES))],
            'target_id' => 'nullable|integer|min:1',
            'priority' => ['sometimes', Rule::in(array_keys(Announcement::PRIORITIES))],
            'expires_at' => 'nullable|date|after:now',
            'is_pinned' => 'boolean',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'string|max:500'
        ];

        // إذا كان التحديث، نضيف قواعد إضافية
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['title'] = 'sometimes|string|max:255';
            $rules['content'] = 'sometimes|string|min:10';
            $rules['target_type'] = ['sometimes', Rule::in(array_keys(Announcement::TARGET_TYPES))];
            $rules['status'] = ['sometimes', Rule::in(array_keys(Announcement::STATUSES))];
        }

        // التحقق من صحة target_id حسب target_type
        if ($this->has('target_type') && $this->has('target_id')) {
            $targetType = $this->input('target_type');
            $targetId = $this->input('target_id');

            if ($targetId !== null) {
                switch ($targetType) {
                    case 'institute':
                        $rules['target_id'] = 'exists:institutes,id';
                        break;
                    case 'department':
                        $rules['target_id'] = 'exists:departments,id';
                        break;
                    case 'course':
                        $rules['target_id'] = 'exists:courses,id';
                        break;
                    case 'teacher':
                        $rules['target_id'] = 'exists:teachers,id';
                        break;
                    case 'general':
                        $rules['target_id'] = 'nullable';
                        break;
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الإعلان مطلوب',
            'title.max' => 'عنوان الإعلان يجب ألا يتجاوز 255 حرف',
            'content.required' => 'محتوى الإعلان مطلوب',
            'content.min' => 'محتوى الإعلان يجب أن يكون على الأقل 10 أحرف',
            'target_type.required' => 'نوع الهدف مطلوب',
            'target_type.in' => 'نوع الهدف المحدد غير صحيح',
            'target_id.exists' => 'الهدف المحدد غير موجود',
            'target_id.integer' => 'معرف الهدف يجب أن يكون رقم صحيح',
            'target_id.min' => 'معرف الهدف يجب أن يكون أكبر من صفر',
            'status.in' => 'حالة الإعلان المحددة غير صحيحة',
            'priority.in' => 'أولوية الإعلان المحددة غير صحيحة',
            'expires_at.date' => 'تاريخ انتهاء الصلاحية يجب أن يكون تاريخ صحيح',
            'expires_at.after' => 'تاريخ انتهاء الصلاحية يجب أن يكون في المستقبل',
            'is_pinned.boolean' => 'حقل التثبيت يجب أن يكون صحيح أو خطأ',
            'attachments.array' => 'المرفقات يجب أن تكون مصفوفة',
            'attachments.max' => 'لا يمكن إرفاق أكثر من 5 ملفات',
            'attachments.*.string' => 'كل مرفق يجب أن يكون نص',
            'attachments.*.max' => 'رابط المرفق يجب ألا يتجاوز 500 حرف'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'عنوان الإعلان',
            'content' => 'محتوى الإعلان',
            'target_type' => 'نوع الهدف',
            'target_id' => 'معرف الهدف',
            'status' => 'حالة الإعلان',
            'priority' => 'أولوية الإعلان',
            'expires_at' => 'تاريخ انتهاء الصلاحية',
            'is_pinned' => 'التثبيت',
            'attachments' => 'المرفقات'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // التحقق من أن target_id مطلوب لبعض أنواع الأهداف
            $targetType = $this->input('target_type');
            $targetId = $this->input('target_id');

            if (in_array($targetType, ['institute', 'department', 'course', 'teacher']) && empty($targetId)) {
                $validator->errors()->add('target_id', 'معرف الهدف مطلوب لهذا النوع من الإعلانات');
            }

            // التحقق من أن target_id غير مطلوب للإعلانات العامة
            if ($targetType === 'general' && !empty($targetId)) {
                $validator->errors()->add('target_id', 'لا يجب تحديد معرف هدف للإعلانات العامة');
            }
        });
    }
}
