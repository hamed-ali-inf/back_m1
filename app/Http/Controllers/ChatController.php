<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\Department;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * عرض قائمة المحادثات للمستخدم الحالي
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        
        $query = Chat::forUser($userId)
                    ->with(['lastMessage.sender', 'participants.user'])
                    ->withCount('messages');

        // فلترة حسب نوع المحادثة
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // فلترة المحادثات الجماعية أو الخاصة
        if ($request->has('is_group')) {
            if ($request->boolean('is_group')) {
                $query->groups();
            } else {
                $query->private();
            }
        }

        // فلترة المحادثات النشطة
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // البحث في المحادثات
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // ترتيب حسب آخر رسالة
        $chats = $query->orderBy('last_message_at', 'desc')
                      ->orderBy('updated_at', 'desc')
                      ->paginate(20);

        // إضافة معلومات المشارك الحالي
        $chats->getCollection()->transform(function ($chat) use ($userId) {
            $participant = $chat->participants->where('user_id', $userId)->first();
            $chat->current_user_participant = $participant;
            return $chat;
        });

        return response()->json([
            'success' => true,
            'data' => $chats,
            'chat_types' => Chat::TYPES
        ]);
    }

    /**
     * إنشاء محادثة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(Chat::TYPES))],
            'title' => 'nullable|string|max:255',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
            'context_id' => 'nullable|integer',
            'context_type' => 'nullable|string|in:course,department,institute',
            'is_group' => 'boolean'
        ]);

        $userId = auth()->id();
        $isGroup = $validated['is_group'] ?? (count($validated['participants']) > 1);

        // للمحادثات الخاصة، التحقق من وجود محادثة مسبقة
        if (!$isGroup && count($validated['participants']) === 1) {
            $otherUserId = $validated['participants'][0];
            $existingChat = Chat::findOrCreatePrivateChat(
                $userId, 
                $otherUserId, 
                $validated['type'],
                $validated['context_id'] ?? null,
                $validated['context_type'] ?? null
            );

            if ($existingChat->wasRecentlyCreated === false) {
                return response()->json([
                    'success' => true,
                    'message' => 'المحادثة موجودة بالفعل',
                    'data' => $existingChat->load(['participants.user', 'lastMessage'])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المحادثة بنجاح',
                'data' => $existingChat->load(['participants.user', 'lastMessage'])
            ], 201);
        }

        // إنشاء محادثة جماعية
        $chat = Chat::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'created_by' => $userId,
            'context_id' => $validated['context_id'] ?? null,
            'context_type' => $validated['context_type'] ?? null,
            'is_group' => $isGroup
        ]);

        // إضافة المنشئ كمدير
        $chat->addParticipant($userId, 'admin');

        // إضافة باقي المشاركين
        foreach ($validated['participants'] as $participantId) {
            if ($participantId !== $userId) {
                $chat->addParticipant($participantId, 'member');
            }
        }

        // إنشاء رسالة نظام
        if ($isGroup) {
            ChatMessage::createSystemMessage($chat->id, 'تم إنشاء المجموعة');
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المحادثة بنجاح',
            'data' => $chat->load(['participants.user', 'lastMessage'])
        ], 201);
    }

    /**
     * عرض تفاصيل محادثة محددة
     */
    public function show(Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من صلاحية الوصول
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه المحادثة'
            ], 403);
        }

        $chat->load([
            'participants.user',
            'messages' => function ($query) {
                $query->notDeleted()
                      ->with(['sender', 'replyTo.sender'])
                      ->orderBy('created_at', 'desc')
                      ->limit(50);
            }
        ]);

        // تحديث آخر قراءة للمستخدم
        $participant = $chat->participants->where('user_id', $userId)->first();
        if ($participant) {
            $participant->updateLastRead();
        }

        return response()->json([
            'success' => true,
            'data' => $chat
        ]);
    }

    /**
     * تحديث معلومات المحادثة
     */
    public function update(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من صلاحية التعديل
        $userRole = $chat->getUserRole($userId);
        if (!in_array($userRole, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل هذه المحادثة'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
            'settings' => 'nullable|array'
        ]);

        $chat->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المحادثة بنجاح',
            'data' => $chat->load(['participants.user'])
        ]);
    }

    /**
     * حذف المحادثة
     */
    public function destroy(Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من صلاحية الحذف (المنشئ أو المدير فقط)
        if ($chat->created_by !== $userId && $chat->getUserRole($userId) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف هذه المحادثة'
            ], 403);
        }

        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحادثة بنجاح'
        ]);
    }

    /**
     * إرسال رسالة في المحادثة
     */
    public function sendMessage(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للمراسلة في هذه المحادثة'
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:chat_messages,id'
        ]);

        $message = ChatMessage::createTextMessage(
            $chat->id,
            $userId,
            $validated['content'],
            $validated['reply_to_id'] ?? null
        );

        // تحديث عدد الرسائل غير المقروءة للمشاركين الآخرين
        $chat->participants()
             ->where('user_id', '!=', $userId)
             ->each(function ($participant) {
                 $participant->incrementUnreadCount();
             });

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => $message->load(['sender', 'replyTo.sender'])
        ], 201);
    }

    /**
     * رفع ملف في المحادثة
     */
    public function uploadFile(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للمراسلة في هذه المحادثة'
            ], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'caption' => 'nullable|string|max:1000'
        ]);

        $file = $validated['file'];
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();

        // حفظ الملف
        $filePath = $file->store('chat_files', 'public');

        $message = ChatMessage::createFileMessage(
            $chat->id,
            $userId,
            $fileName,
            $filePath,
            $fileType,
            $fileSize,
            $validated['caption'] ?? $fileName
        );

        // تحديث عدد الرسائل غير المقروءة للمشاركين الآخرين
        $chat->participants()
             ->where('user_id', '!=', $userId)
             ->each(function ($participant) {
                 $participant->incrementUnreadCount();
             });

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح',
            'data' => $message->load(['sender'])
        ], 201);
    }

    /**
     * إضافة مشارك جديد للمحادثة
     */
    public function addParticipant(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من صلاحية الإضافة
        $userRole = $chat->getUserRole($userId);
        if (!in_array($userRole, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإضافة مشاركين'
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => ['sometimes', Rule::in(array_keys(ChatParticipant::ROLES))]
        ]);

        $newUserId = $validated['user_id'];
        $role = $validated['role'] ?? 'member';

        // التحقق من عدم وجود المستخدم مسبقاً
        if ($chat->hasParticipant($newUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم مشارك بالفعل في المحادثة'
            ], 400);
        }

        $participant = $chat->addParticipant($newUserId, $role);
        $user = User::find($newUserId);

        // إنشاء رسالة نظام
        ChatMessage::createSystemMessage($chat->id, "انضم {$user->name} إلى المحادثة");

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المشارك بنجاح',
            'data' => $participant->load('user')
        ]);
    }

    /**
     * إزالة مشارك من المحادثة
     */
    public function removeParticipant(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();
        $targetUserId = $request->input('user_id');

        // التحقق من صلاحية الإزالة
        $userRole = $chat->getUserRole($userId);
        $targetRole = $chat->getUserRole($targetUserId);

        if ($userId !== $targetUserId && !in_array($userRole, ['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإزالة هذا المشارك'
            ], 403);
        }

        // لا يمكن إزالة المدير إلا من قبل نفسه
        if ($targetRole === 'admin' && $userId !== $targetUserId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إزالة مدير المحادثة'
            ], 403);
        }

        $user = User::find($targetUserId);
        $chat->removeParticipant($targetUserId);

        // إنشاء رسالة نظام
        if ($userId === $targetUserId) {
            ChatMessage::createSystemMessage($chat->id, "غادر {$user->name} المحادثة");
        } else {
            ChatMessage::createSystemMessage($chat->id, "تم إزالة {$user->name} من المحادثة");
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة المشارك بنجاح'
        ]);
    }

    /**
     * تحديد الرسائل كمقروءة
     */
    public function markAsRead(Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه المحادثة'
            ], 403);
        }

        // تحديث آخر قراءة للمستخدم
        $participant = $chat->participants()->where('user_id', $userId)->first();
        if ($participant) {
            $participant->updateLastRead();
        }

        // تحديد جميع الرسائل كمقروءة
        $chat->messages()
             ->where('sender_id', '!=', $userId)
             ->get()
             ->each(function ($message) use ($userId) {
                 $message->markAsReadBy($userId);
             });

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الرسائل كمقروءة'
        ]);
    }

    /**
     * البحث في الرسائل
     */
    public function searchMessages(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه المحادثة'
            ], 403);
        }

        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'type' => ['nullable', Rule::in(array_keys(ChatMessage::TYPES))]
        ]);

        $query = $chat->messages()
                     ->notDeleted()
                     ->search($validated['query'])
                     ->with(['sender']);

        if (isset($validated['type'])) {
            $query->byType($validated['type']);
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * إنشاء محادثة بين طالب وأستاذ
     */
    public function createStudentTeacherChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'course_id' => 'required|exists:courses,id'
        ]);

        $studentId = auth()->id();
        $teacherId = $validated['teacher_id'];
        $courseId = $validated['course_id'];

        // التحقق من أن المستخدم طالب
        $student = Student::where('user_id', $studentId)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن تكون طالباً لإنشاء هذه المحادثة'
            ], 403);
        }

        // البحث عن محادثة موجودة أو إنشاء جديدة
        $chat = Chat::findOrCreatePrivateChat(
            $studentId,
            $teacherId,
            'student_teacher',
            $courseId,
            'course'
        );

        return response()->json([
            'success' => true,
            'message' => $chat->wasRecentlyCreated ? 'تم إنشاء المحادثة بنجاح' : 'المحادثة موجودة بالفعل',
            'data' => $chat->load(['participants.user', 'lastMessage'])
        ]);
    }

    /**
     * الحصول على إحصائيات المحادثات
     */
    public function getStatistics(): JsonResponse
    {
        $userId = auth()->id();

        $stats = [
            'total_chats' => Chat::forUser($userId)->count(),
            'active_chats' => Chat::forUser($userId)->active()->count(),
            'group_chats' => Chat::forUser($userId)->groups()->count(),
            'private_chats' => Chat::forUser($userId)->private()->count(),
            'unread_messages' => ChatParticipant::where('user_id', $userId)->sum('unread_count'),
            'pinned_chats' => ChatParticipant::where('user_id', $userId)->pinned()->count(),
            'archived_chats' => ChatParticipant::where('user_id', $userId)->archived()->count()
        ];

        // إحصائيات حسب نوع المحادثة
        $byType = [];
        foreach (Chat::TYPES as $key => $name) {
            $byType[$key] = [
                'name' => $name,
                'count' => Chat::forUser($userId)->byType($key)->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'general_stats' => $stats,
                'by_type' => $byType
            ]
        ]);
    }
}
