<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\CourseFile;
use App\Models\ChatMessage;
use App\Models\Announcement;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * الحصول على المعلم الحالي من المستخدم المسجل
     */
    protected function getCurrentTeacher()
    {
        $user = Auth::user();
        return Teacher::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * ============================================
     * الجدول الزمني - عرض الجدول الخاص بالمحاضرات
     * ============================================
     */
    
    /**
     * عرض الجدول الزمني لجميع المحاضرات التي يقوم المعلم بتدريسها
     */
    public function getSchedule(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            // الحصول على جميع الجداول للمقررات التي يدرسها المعلم
            $schedules = Schedule::whereHas('course', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->with(['course' => function ($query) {
                $query->select('id', 'name', 'level', 'teacher_id');
            }])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $schedules,
                'message' => 'تم جلب الجدول الزمني بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الجدول الزمني',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * المواد الدراسية والدروس - إدارة الملفات
     * ============================================
     */

    /**
     * عرض جميع المقررات التي يدرسها المعلم
     */
    public function getCourses()
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            $courses = Course::where('teacher_id', $teacher->id)
                ->withCount('files')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'تم جلب المقررات بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب المقررات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض ملفات مقرر معين
     */
    public function getCourseFiles($courseId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            // التحقق من أن المقرر يخص هذا المعلم
            $course = Course::where('id', $courseId)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            $files = CourseFile::where('course_id', $courseId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $files,
                'course' => $course,
                'message' => 'تم جلب الملفات بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الملفات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفع ملف أو درس جديد لمقرر معين
     */
    public function uploadFile(Request $request, $courseId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            // التحقق من أن المقرر يخص هذا المعلم
            $course = Course::where('id', $courseId)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('course_files/' . $courseId, $fileName, 'public');

            $courseFile = CourseFile::create([
                'course_id' => $courseId,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $courseFile,
                'message' => 'تم رفع الملف بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في رفع الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث ملف أو درس موجود
     */
    public function updateFile(Request $request, $courseId, $fileId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            // التحقق من أن المقرر يخص هذا المعلم
            $course = Course::where('id', $courseId)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            $courseFile = CourseFile::where('id', $fileId)
                ->where('course_id', $courseId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'sometimes|file|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            // تحديث الملف إذا تم رفعه
            if ($request->hasFile('file')) {
                // حذف الملف القديم
                Storage::disk('public')->delete($courseFile->file_path);
                
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('course_files/' . $courseId, $fileName, 'public');
                
                $courseFile->update([
                    'file_path' => $filePath,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ]);
            }

            // تحديث البيانات الأخرى
            $courseFile->update($request->only(['title', 'description']));

            return response()->json([
                'success' => true,
                'data' => $courseFile->fresh(),
                'message' => 'تم تحديث الملف بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف ملف أو درس
     */
    public function deleteFile($courseId, $fileId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            
            // التحقق من أن المقرر يخص هذا المعلم
            $course = Course::where('id', $courseId)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            $courseFile = CourseFile::where('id', $fileId)
                ->where('course_id', $courseId)
                ->firstOrFail();

            // حذف الملف من التخزين
            Storage::disk('public')->delete($courseFile->file_path);
            
            // حذف السجل من قاعدة البيانات
            $courseFile->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملف بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف الملف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * الدردشة - استقبال الرسائل والرد عليها
     * ============================================
     */

    /**
     * عرض جميع الرسائل الواردة للمعلم
     */
    public function getReceivedMessages()
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $messages = ChatMessage::where('receiver_id', $user->id)
                ->with(['sender' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
                'message' => 'تم جلب الرسائل بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الرسائل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض رسالة معينة
     */
    public function getMessage($messageId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $message = ChatMessage::where('id', $messageId)
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->with(['sender' => function ($query) {
                    $query->select('id', 'name', 'email');
                }, 'receiver' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->firstOrFail();

            // تحديث حالة القراءة إذا كان المستقبل هو المعلم
            if ($message->receiver_id == $user->id && !$message->read_status) {
                $message->update(['read_status' => true]);
            }

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'تم جلب الرسالة بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الرسالة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال رد على رسالة
     */
    public function sendReply(Request $request, $messageId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            // الحصول على الرسالة الأصلية
            $originalMessage = ChatMessage::where('id', $messageId)
                ->where('receiver_id', $user->id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            // إنشاء رد جديد
            $reply = ChatMessage::create([
                'sender_id' => $user->id,
                'receiver_id' => $originalMessage->sender_id,
                'message' => $request->message,
                'read_status' => false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $reply->load(['sender', 'receiver']),
                'message' => 'تم إرسال الرد بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الرد',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال رسالة جديدة
     */
    public function sendMessage(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            $message = ChatMessage::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'read_status' => false,
            ]);

            return response()->json([
                'success' => true,
                'data' => $message->load(['sender', 'receiver']),
                'message' => 'تم إرسال الرسالة بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الرسالة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * الإعلانات - إرسال إعلانات للطلبة
     * ============================================
     */

    /**
     * عرض جميع الإعلانات التي أرسلها المعلم
     */
    public function getAnnouncements()
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $announcements = Announcement::where('sender_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $announcements,
                'message' => 'تم جلب الإعلانات بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الإعلانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إعلان للطلبة الذين يدرسون المواد التي يقوم المعلم بتدريسها
     */
    public function sendAnnouncement(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'course_id' => 'required|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            // التحقق من أن المقرر يخص هذا المعلم
            $course = Course::where('id', $request->course_id)
                ->where('teacher_id', $teacher->id)
                ->firstOrFail();

            // إنشاء إعلان موجه للطلبة الذين يدرسون هذا المقرر
            // target_type = 'course' و target_id = course_id
            $announcement = Announcement::create([
                'sender_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'target_type' => 'course',
                'target_id' => $course->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $announcement,
                'message' => 'تم إرسال الإعلان بنجاح للطلبة المسجلين في المقرر'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الإعلان',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إعلان لمستوى معين أو قسم معين
     */
    public function sendAnnouncementToTarget(Request $request)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'target_type' => 'required|in:level,group,department,institute,all',
                'target_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            $announcement = Announcement::create([
                'sender_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'target_type' => $request->target_type,
                'target_id' => $request->target_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $announcement,
                'message' => 'تم إرسال الإعلان بنجاح'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الإعلان',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث إعلان
     */
    public function updateAnnouncement(Request $request, $announcementId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $announcement = Announcement::where('id', $announcementId)
                ->where('sender_id', $user->id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحقق من البيانات فشل',
                    'errors' => $validator->errors()
                ], 422);
            }

            $announcement->update($request->only(['title', 'content']));

            return response()->json([
                'success' => true,
                'data' => $announcement->fresh(),
                'message' => 'تم تحديث الإعلان بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الإعلان',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف إعلان
     */
    public function deleteAnnouncement($announcementId)
    {
        try {
            $teacher = $this->getCurrentTeacher();
            $user = Auth::user();

            $announcement = Announcement::where('id', $announcementId)
                ->where('sender_id', $user->id)
                ->firstOrFail();

            $announcement->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الإعلان بنجاح'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف الإعلان',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
