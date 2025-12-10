<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AnnouncementController extends Controller
{
    /**
     * عرض قائمة الإعلانات
     */
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::with(['sender']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب نوع الهدف
        if ($request->has('target_type')) {
            $query->byTargetType($request->target_type);
        }

        // فلترة حسب الهدف المحدد
        if ($request->has('target_id') && $request->has('target_type')) {
            $query->byTarget($request->target_type, $request->target_id);
        }

        // فلترة حسب الأولوية
        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        // فلترة حسب المرسل
        if ($request->has('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        // فلترة الإعلانات النشطة فقط
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // فلترة للطالب المحدد
        if ($request->has('student_id')) {
            $query->forStudent($request->student_id);
        }

        // ترتيب حسب الأولوية والتثبيت
        $announcements = $query->orderByPriority()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $announcements,
            'target_types' => Announcement::TARGET_TYPES,
            'statuses' => Announcement::STATUSES,
            'priorities' => Announcement::PRIORITIES
        ]);
    }

    /**
     * إنشاء إعلان جديد
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => ['required', Rule::in(array_keys(Announcement::TARGET_TYPES))],
            'target_id' => 'nullable|integer',
            'priority' => ['sometimes', Rule::in(array_keys(Announcement::PRIORITIES))],
            'expires_at' => 'nullable|date|after:now',
            'is_pinned' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string'
        ]);

        $validated['sender_id'] = auth()->id();
        $validated['priority'] = $validated['priority'] ?? 'normal';

        $announcement = Announcement::create($validated);
        $announcement->load(['sender']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإعلان بنجاح',
            'data' => $announcement
        ], 201);
    }

    /**
     * عرض تفاصيل إعلان محدد
     */
    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load(['sender']);
        
        // زيادة عدد المشاهدات
        $announcement->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $announcement
        ]);
    }

    /**
     * تحديث الإعلان
     */
    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'target_type' => ['sometimes', Rule::in(array_keys(Announcement::TARGET_TYPES))],
            'target_id' => 'nullable|integer',
            'status' => ['sometimes', Rule::in(array_keys(Announcement::STATUSES))],
            'priority' => ['sometimes', Rule::in(array_keys(Announcement::PRIORITIES))],
            'expires_at' => 'nullable|date|after:now',
            'is_pinned' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string'
        ]);

        // إذا تم تغيير الحالة إلى منشور
        if (isset($validated['status']) && $validated['status'] === 'published' && $announcement->status !== 'published') {
            $validated['published_at'] = now();
        }

        $announcement->update($validated);
        $announcement->load(['sender']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعلان بنجاح',
            'data' => $announcement
        ]);
    }

    /**
     * نشر الإعلان
     */
    public function publish(Announcement $announcement): JsonResponse
    {
        if ($announcement->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'الإعلان منشور بالفعل'
            ], 400);
        }

        $announcement->publish();
        $announcement->load(['sender']);

        return response()->json([
            'success' => true,
            'message' => 'تم نشر الإعلان بنجاح',
            'data' => $announcement
        ]);
    }

    /**
     * أرشفة الإعلان
     */
    public function archive(Announcement $announcement): JsonResponse
    {
        if ($announcement->status === 'archived') {
            return response()->json([
                'success' => false,
                'message' => 'الإعلان مؤرشف بالفعل'
            ], 400);
        }

        $announcement->archive();
        $announcement->load(['sender']);

        return response()->json([
            'success' => true,
            'message' => 'تم أرشفة الإعلان بنجاح',
            'data' => $announcement
        ]);
    }

    /**
     * تثبيت/إلغاء تثبيت الإعلان
     */
    public function togglePin(Announcement $announcement): JsonResponse
    {
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);
        $announcement->load(['sender']);

        $message = $announcement->is_pinned ? 'تم تثبيت الإعلان' : 'تم إلغاء تثبيت الإعلان';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $announcement
        ]);
    }

    /**
     * حذف الإعلان
     */
    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعلان بنجاح'
        ]);
    }

    /**
     * الإعلانات للطالب المحدد
     */
    public function getForStudent(Request $request, $studentId): JsonResponse
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'الطالب غير موجود'
            ], 404);
        }

        $announcements = Announcement::active()
            ->forStudent($studentId)
            ->with(['sender'])
            ->orderByPriority()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }

    /**
     * الإعلانات العاجلة
     */
    public function getUrgent(): JsonResponse
    {
        $announcements = Announcement::active()
            ->byPriority('urgent')
            ->with(['sender'])
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }

    /**
     * الإعلانات المثبتة
     */
    public function getPinned(): JsonResponse
    {
        $announcements = Announcement::active()
            ->pinned()
            ->with(['sender'])
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }

    /**
     * إحصائيات الإعلانات
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Announcement::count(),
            'published' => Announcement::where('status', 'published')->count(),
            'draft' => Announcement::where('status', 'draft')->count(),
            'archived' => Announcement::where('status', 'archived')->count(),
            'expired' => Announcement::where('status', 'expired')->count(),
            'pinned' => Announcement::where('is_pinned', true)->count(),
        ];

        // إحصائيات حسب الأولوية
        $byPriority = [];
        foreach (Announcement::PRIORITIES as $key => $name) {
            $byPriority[$key] = [
                'name' => $name,
                'count' => Announcement::byPriority($key)->count()
            ];
        }

        // إحصائيات حسب نوع الهدف
        $byTargetType = [];
        foreach (Announcement::TARGET_TYPES as $key => $name) {
            $byTargetType[$key] = [
                'name' => $name,
                'count' => Announcement::byTargetType($key)->count()
            ];
        }

        // أكثر الإعلانات مشاهدة
        $mostViewed = Announcement::orderBy('views_count', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'views_count']);

        return response()->json([
            'success' => true,
            'data' => [
                'general_stats' => $stats,
                'by_priority' => $byPriority,
                'by_target_type' => $byTargetType,
                'most_viewed' => $mostViewed
            ]
        ]);
    }

    /**
     * البحث في الإعلانات
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'target_type' => ['nullable', Rule::in(array_keys(Announcement::TARGET_TYPES))],
            'priority' => ['nullable', Rule::in(array_keys(Announcement::PRIORITIES))],
            'status' => ['nullable', Rule::in(array_keys(Announcement::STATUSES))]
        ]);

        $query = Announcement::with(['sender'])
            ->where(function ($q) use ($validated) {
                $q->where('title', 'like', '%' . $validated['query'] . '%')
                  ->orWhere('content', 'like', '%' . $validated['query'] . '%');
            });

        // تطبيق الفلاتر الإضافية
        if (isset($validated['target_type'])) {
            $query->byTargetType($validated['target_type']);
        }

        if (isset($validated['priority'])) {
            $query->byPriority($validated['priority']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $announcements = $query->orderByPriority()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }

    /**
     * تحديث الإعلانات المنتهية الصلاحية
     */
    public function updateExpired(): JsonResponse
    {
        Announcement::updateExpiredAnnouncements();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الإعلانات المنتهية الصلاحية'
        ]);
    }
}
