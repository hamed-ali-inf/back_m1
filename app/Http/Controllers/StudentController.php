<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\DocumentRequest;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * عرض جميع الطلبة أو البحث
     */
    public function index(Request $request)
    {
        $query = Student::with(['user', 'department', 'institute']);

        // البحث بالاسم
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // البحث بالبريد الإلكتروني
        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        // البحث بالمستوى
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // البحث بالفوج
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        // البحث برقم القسم
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // البحث برقم المعهد
        if ($request->has('institute_id')) {
            $query->where('institute_id', $request->institute_id);
        }

        $students = $query->get();
        
        return response()->json($students);
    }

    /**
     * عرض بيانات طالب معين
     */
    public function show($id)
    {
        $student = Student::with(['user', 'department', 'institute', 'documentRequests'])
            ->findOrFail($id);
        
        return response()->json($student);
    }

    /**
     * إضافة طالب جديد
     */
    public function store(Request $request)
    {
        // إذا تم توفير user_id، استخدمه. وإلا أنشئ user جديد
        if ($request->has('user_id')) {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email',
                'phone' => 'nullable|string|max:20',
                'level' => 'required|string|max:255',
                'group' => 'required|string|max:255',
                'department_id' => 'nullable|exists:departments,id',
                'institute_id' => 'nullable|exists:institutes,id',
            ]);
            
            $student = Student::create($validated);
        } else {
            // إنشاء user جديد تلقائياً
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|unique:students,email',
                'password' => 'required|string|min:6',
                'phone' => 'nullable|string|max:20',
                'level' => 'required|string|max:255',
                'group' => 'required|string|max:255',
                'department_id' => 'nullable|exists:departments,id',
                'institute_id' => 'nullable|exists:institutes,id',
                'role' => 'sometimes|in:student,teacher,section_head,institute_head'
            ]);

            // إنشاء User جديد
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'] ?? 'student'
            ]);

            // إنشاء Student
            $student = Student::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'level' => $validated['level'],
                'group' => $validated['group'],
                'department_id' => $validated['department_id'] ?? null,
                'institute_id' => $validated['institute_id'] ?? null,
            ]);
        }
        
        return response()->json([
            'message' => 'تم إنشاء الطالب بنجاح',
            'student' => $student->load(['user', 'department', 'institute'])
        ], 201);
    }

    /**
     * تحديث بيانات طالب
     */
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:students,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'level' => 'sometimes|required|string|max:255',
            'group' => 'sometimes|required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'institute_id' => 'nullable|exists:institutes,id',
        ]);

        $student->update($validated);
        
        return response()->json($student->load(['user', 'department', 'institute']));
    }

    /**
     * حذف طالب
     */
    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        
        return response()->json(['message' => 'تم حذف الطالب بنجاح'], 200);
    }

    /**
     * عرض الجدول الزمني للطالب
     */
    public function getSchedule($id)
    {
        $student = Student::findOrFail($id);
        
        // الحصول على الجداول الزمنية للدورات التي تتناسب مع مستوى الطالب
        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.id')
            ->where('courses.level', $student->level)
            ->select(
                'schedules.id',
                'schedules.day',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.classroom',
                'courses.name as course_name',
                'courses.description as course_description'
            )
            ->get();
        
        return response()->json([
            'student' => $student->name,
            'level' => $student->level,
            'group' => $student->group,
            'schedules' => $schedules
        ]);
    }

    /**
     * عرض الدورات للطالب
     */
    public function getCourses($id)
    {
        $student = Student::with('department')->findOrFail($id);
        
        // الحصول على الدورات التي تتناسب مع مستوى الطالب
        $courses = DB::table('courses')
            ->join('teachers', 'courses.teacher_id', '=', 'teachers.id')
            ->leftJoin('departments', 'teachers.department_id', '=', 'departments.id')
            ->where('courses.level', $student->level)
            ->select(
                'courses.id',
                'courses.name',
                'courses.description',
                'courses.level',
                'teachers.name as teacher_name',
                'teachers.email as teacher_email',
                'departments.name as department_name'
            )
            ->get();
        
        return response()->json([
            'student' => $student->name,
            'level' => $student->level,
            'courses' => $courses
        ]);
    }

    /**
     * طلب وثيقة
     */
    public function requestDocument(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'document_type' => 'required|string|max:255',
        ]);

        $documentRequest = DocumentRequest::create([
            'student_id' => $student->id,
            'document_type' => $validated['document_type'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الوثيقة بنجاح',
            'request' => $documentRequest
        ], 201);
    }

    /**
     * عرض الإعلانات للطالب
     */
    public function getAnnouncements($id)
    {
        $student = Student::with(['department', 'institute'])->findOrFail($id);
        
        $announcements = DB::table('announcements')
            ->where(function($query) use ($student) {
                $query->where('level', $student->level)
                      ->orWhere('department_id', $student->department_id)
                      ->orWhere('institute_id', $student->institute_id)
                      ->orWhereNull('level')
                      ->orWhereNull('department_id')
                      ->orWhereNull('institute_id');
            })
            ->join('users', 'announcements.user_id', '=', 'users.id')
            ->select(
                'announcements.id',
                'announcements.title',
                'announcements.content',
                'announcements.level',
                'announcements.created_at',
                'users.name as author_name'
            )
            ->orderBy('announcements.created_at', 'desc')
            ->get();
        
        return response()->json([
            'student' => $student->name,
            'announcements' => $announcements
        ]);
    }

    /**
     * إرسال رسالة لأستاذ
     */
    public function sendMessageToTeacher(Request $request, $id, $teacherId)
    {
        $student = Student::findOrFail($id);
        $teacher = \App\Models\Teacher::findOrFail($teacherId);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // الحصول على user_id للأستاذ والطالب
        $studentUser = $student->user;
        $teacherUser = $teacher->user;

        if (!$studentUser || !$teacherUser) {
            return response()->json([
                'message' => 'الطالب أو الأستاذ غير مرتبط بحساب مستخدم'
            ], 400);
        }

        $chatMessage = ChatMessage::create([
            'sender_id' => $studentUser->id,
            'receiver_id' => $teacherUser->id,
            'message' => $validated['message'],
            'read_status' => false,
        ]);

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'chat_message' => $chatMessage
        ], 201);
    }

    /**
     * عرض طلبات الوثائق للطالب
     */
    public function getDocumentRequests($id)
    {
        $student = Student::findOrFail($id);
        $documentRequests = $student->documentRequests()->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'student' => $student->name,
            'document_requests' => $documentRequests
        ]);
    }
}
