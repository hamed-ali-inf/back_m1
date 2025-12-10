<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentRequest;

class DocumentRequestRequest extends FormRequest
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
            'student_id' => 'required|exists:students,id',
            'document_type' => ['required', Rule::in(array_keys(DocumentRequest::DOCUMENT_TYPES))],
            'notes' => 'nullable|string|max:1000'
        ];

        // إذا كان التحديث، نضيف قواعد إضافية
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['status'] = ['sometimes', Rule::in(array_keys(DocumentRequest::STATUSES))];
            $rules['rejection_reason'] = 'nullable|string|max:500';
            $rules['student_id'] = 'sometimes|exists:students,id';
            $rules['document_type'] = ['sometimes', Rule::in(array_keys(DocumentRequest::DOCUMENT_TYPES))];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.exists' => 'الطالب المحدد غير موجود',
            'document_type.required' => 'نوع الوثيقة مطلوب',
            'document_type.in' => 'نوع الوثيقة المحدد غير صحيح',
            'status.in' => 'حالة الطلب المحددة غير صحيحة',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
            'rejection_reason.max' => 'سبب الرفض يجب ألا يتجاوز 500 حرف'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'معرف الطالب',
            'document_type' => 'نوع الوثيقة',
            'status' => 'حالة الطلب',
            'notes' => 'الملاحظات',
            'rejection_reason' => 'سبب الرفض'
        ];
    }
}
