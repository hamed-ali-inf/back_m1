<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DocumentRequestController extends Controller
{
    /**
     * عرض قائمة طلبات الوثائق
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentRequest::with(['student', 'approvedBy']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // فلترة حسب نوع الوثيقة
        if ($request->has('document_type')) {
            $query->byDocumentType($request->document_type);
        }

        // فلترة حسب الطالب
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $documentRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $documentRequests,
            'document_types' => DocumentRequest::DOCUMENT_TYPES,
            'statuses' => DocumentRequest::STATUSES
        ]);
    }

    /**
     * إنشاء طلب وثيقة جديد
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'document_type' => ['required', Rule::in(array_keys(DocumentRequest::DOCUMENT_TYPES))],
            'notes' => 'nullable|string|max:1000'
        ]);

        $documentRequest = DocumentRequest::create($validated);
        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء طلب الوثيقة بنجاح',
            'data' => $documentRequest
        ], 201);
    }

    /**
     * عرض تفاصيل طلب وثيقة محدد
     */
    public function show(DocumentRequest $documentRequest): JsonResponse
    {
        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => $documentRequest
        ]);
    }

    /**
     * تحديث طلب الوثيقة
     */
    public function update(Request $request, DocumentRequest $documentRequest): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['sometimes', Rule::in(array_keys(DocumentRequest::DOCUMENT_TYPES))],
            'status' => ['sometimes', Rule::in(array_keys(DocumentRequest::STATUSES))],
            'notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        // إذا تم تغيير الحالة إلى موافق عليه
        if (isset($validated['status']) && $validated['status'] === 'approved') {
            $validated['approved_at'] = now();
            $validated['approved_by'] = auth()->id();
        }

        // إذا تم تغيير الحالة إلى مكتمل
        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $documentRequest->update($validated);
        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طلب الوثيقة بنجاح',
            'data' => $documentRequest
        ]);
    }

    /**
     * الموافقة على طلب الوثيقة
     */
    public function approve(DocumentRequest $documentRequest): JsonResponse
    {
        if ($documentRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الموافقة على هذا الطلب'
            ], 400);
        }

        $documentRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);

        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على طلب الوثيقة بنجاح',
            'data' => $documentRequest
        ]);
    }

    /**
     * رفض طلب الوثيقة
     */
    public function reject(Request $request, DocumentRequest $documentRequest): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($documentRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذا الطلب'
            ], 400);
        }

        $documentRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب الوثيقة',
            'data' => $documentRequest
        ]);
    }

    /**
     * تسليم الوثيقة (تغيير الحالة إلى مكتمل)
     */
    public function complete(DocumentRequest $documentRequest): JsonResponse
    {
        if ($documentRequest->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن يكون الطلب موافق عليه أولاً'
            ], 400);
        }

        $documentRequest->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسليم الوثيقة بنجاح',
            'data' => $documentRequest
        ]);
    }

    /**
     * إلغاء طلب الوثيقة
     */
    public function cancel(DocumentRequest $documentRequest): JsonResponse
    {
        if (in_array($documentRequest->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب'
            ], 400);
        }

        $documentRequest->update(['status' => 'cancelled']);
        $documentRequest->load(['student', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب الوثيقة',
            'data' => $documentRequest
        ]);
    }

    /**
     * حذف طلب الوثيقة
     */
    public function destroy(DocumentRequest $documentRequest): JsonResponse
    {
        $documentRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف طلب الوثيقة بنجاح'
        ]);
    }

    /**
     * إحصائيات طلبات الوثائق
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => DocumentRequest::count(),
            'pending' => DocumentRequest::pending()->count(),
            'approved' => DocumentRequest::byStatus('approved')->count(),
            'completed' => DocumentRequest::completed()->count(),
            'rejected' => DocumentRequest::byStatus('rejected')->count(),
            'cancelled' => DocumentRequest::byStatus('cancelled')->count(),
        ];

        // إحصائيات حسب نوع الوثيقة
        $byDocumentType = [];
        foreach (DocumentRequest::DOCUMENT_TYPES as $key => $name) {
            $byDocumentType[$key] = [
                'name' => $name,
                'count' => DocumentRequest::byDocumentType($key)->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'general_stats' => $stats,
                'by_document_type' => $byDocumentType
            ]
        ]);
    }
}
