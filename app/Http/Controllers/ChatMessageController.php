<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ChatMessageController extends Controller
{
    /**
     * عرض رسائل المحادثة مع التصفح
     */
    public function index(Request $request, Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه المحادثة'
            ], 403);
        }

        $query = $chat->messages()
                     ->notDeleted()
                     ->with(['sender', 'replyTo.sender']);

        // فلترة حسب نوع الرسالة
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // فلترة الملفات فقط
        if ($request->boolean('files_only')) {
            $query->files();
        }

        // البحث في المحتوى
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // فلترة حسب المرسل
        if ($request->has('sender_id')) {
            $query->fromUser($request->sender_id);
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * عرض تفاصيل رسالة محددة
     */
    public function show(ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$message->chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه الرسالة'
            ], 403);
        }

        $message->load(['sender', 'replyTo.sender', 'replies.sender']);

        // تحديد الرسالة كمقروءة
        $message->markAsReadBy($userId);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * تعديل رسالة
     */
    public function update(Request $request, ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من ملكية الرسالة
        if ($message->sender_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل رسالة شخص آخر'
            ], 403);
        }

        // التحقق من نوع الرسالة (النصية فقط قابلة للتعديل)
        if ($message->type !== 'text') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل هذا النوع من الرسائل'
            ], 400);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        $message->editContent($validated['content']);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الرسالة بنجاح',
            'data' => $message->load(['sender', 'replyTo.sender'])
        ]);
    }

    /**
     * حذف رسالة
     */
    public function destroy(ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من ملكية الرسالة أو صلاحية الإدارة
        $userRole = $message->chat->getUserRole($userId);
        $canDelete = $message->sender_id === $userId || in_array($userRole, ['admin', 'moderator']);

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف هذه الرسالة'
            ], 403);
        }

        $message->softDelete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الرسالة بنجاح'
        ]);
    }

    /**
     * الرد على رسالة
     */
    public function reply(Request $request, ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$message->chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للرد في هذه المحادثة'
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        $reply = ChatMessage::createTextMessage(
            $message->chat_id,
            $userId,
            $validated['content'],
            $message->id
        );

        // تحديث عدد الرسائل غير المقروءة للمشاركين الآخرين
        $message->chat->participants()
                     ->where('user_id', '!=', $userId)
                     ->each(function ($participant) {
                         $participant->incrementUnreadCount();
                     });

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرد بنجاح',
            'data' => $reply->load(['sender', 'replyTo.sender'])
        ], 201);
    }

    /**
     * تحديد رسالة كمقروءة
     */
    public function markAsRead(ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$message->chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه الرسالة'
            ], 403);
        }

        $message->markAsReadBy($userId);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الرسالة كمقروءة'
        ]);
    }

    /**
     * الحصول على الردود على رسالة
     */
    public function getReplies(ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$message->chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه الرسالة'
            ], 403);
        }

        $replies = $message->replies()
                          ->notDeleted()
                          ->with(['sender'])
                          ->orderBy('created_at', 'asc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $replies
        ]);
    }

    /**
     * تحميل ملف من رسالة
     */
    public function downloadFile(ChatMessage $message): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$message->chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذا الملف'
            ], 403);
        }

        // التحقق من وجود ملف
        if (!$message->is_file || !$message->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الرسالة لا تحتوي على ملف'
            ], 400);
        }

        $filePath = storage_path('app/public/' . $message->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'الملف غير موجود'
            ], 404);
        }

        return response()->download($filePath, $message->file_name);
    }

    /**
     * البحث في الرسائل عبر جميع المحادثات
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'type' => ['nullable', Rule::in(array_keys(ChatMessage::TYPES))],
            'chat_type' => ['nullable', Rule::in(array_keys(Chat::TYPES))]
        ]);

        // الحصول على معرفات المحادثات التي يشارك فيها المستخدم
        $chatIds = Chat::forUser($userId)->pluck('id');

        $query = ChatMessage::whereIn('chat_id', $chatIds)
                           ->notDeleted()
                           ->search($validated['query'])
                           ->with(['sender', 'chat']);

        // فلترة حسب نوع الرسالة
        if (isset($validated['type'])) {
            $query->byType($validated['type']);
        }

        // فلترة حسب نوع المحادثة
        if (isset($validated['chat_type'])) {
            $query->whereHas('chat', function ($q) use ($validated) {
                $q->byType($validated['chat_type']);
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * الحصول على إحصائيات الرسائل
     */
    public function getStatistics(Chat $chat): JsonResponse
    {
        $userId = auth()->id();

        // التحقق من المشاركة في المحادثة
        if (!$chat->hasParticipant($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذه المحادثة'
            ], 403);
        }

        $stats = [
            'total_messages' => $chat->messages()->notDeleted()->count(),
            'text_messages' => $chat->messages()->notDeleted()->textMessages()->count(),
            'file_messages' => $chat->messages()->notDeleted()->files()->count(),
            'system_messages' => $chat->messages()->notDeleted()->byType('system')->count(),
            'my_messages' => $chat->messages()->notDeleted()->fromUser($userId)->count(),
        ];

        // إحصائيات حسب نوع الرسالة
        $byType = [];
        foreach (ChatMessage::TYPES as $key => $name) {
            $byType[$key] = [
                'name' => $name,
                'count' => $chat->messages()->notDeleted()->byType($key)->count()
            ];
        }

        // أكثر المشاركين نشاطاً
        $mostActive = $chat->messages()
                          ->notDeleted()
                          ->selectRaw('sender_id, COUNT(*) as message_count')
                          ->groupBy('sender_id')
                          ->orderBy('message_count', 'desc')
                          ->limit(5)
                          ->with('sender')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'general_stats' => $stats,
                'by_type' => $byType,
                'most_active_users' => $mostActive
            ]
        ]);
    }
}
