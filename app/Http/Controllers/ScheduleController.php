<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * عرض جميع الجداول الزمنية أو البحث
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['course.teacher', 'course']);

        // البحث حسب الدورة
        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // البحث حسب اليوم
        if ($request->has('day')) {
            $query->where('day', $request->day);
        }

        // البحث حسب الأستاذ (عبر الدورة)
        if ($request->has('teacher_id')) {
            $query->whereHas('course', function($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        // البحث حسب المستوى (عبر الدورة)
        if ($request->has('level')) {
            $query->whereHas('course', function($q) use ($request) {
                $q->where('level', $request->level);
            });
        }

        // الترتيب حسب اليوم ثم وقت البداية
        $schedules = $query->orderByRaw("
            CASE day
                WHEN 'السبت' THEN 1
                WHEN 'الأحد' THEN 2
                WHEN 'الإثنين' THEN 3
                WHEN 'الثلاثاء' THEN 4
                WHEN 'الأربعاء' THEN 5
                WHEN 'الخميس' THEN 6
                WHEN 'الجمعة' THEN 7
            END
        ")
        ->orderBy('start_time')
        ->get();

        return response()->json([
            'data' => $schedules,
            'count' => $schedules->count()
        ]);
    }

    /**
     * عرض جدول زمني معين
     */
    public function show($id)
    {
        $schedule = Schedule::with(['course.teacher', 'course'])
            ->findOrFail($id);
        
        return response()->json([
            'data' => $schedule
        ]);
    }

    /**
     * إضافة جدول زمني جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'day' => 'required|in:السبت,الأحد,الإثنين,الثلاثاء,الأربعاء,الخميس,الجمعة',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من عدم وجود تعارض في الجدول الزمني
        $conflict = Schedule::where('course_id', $validated['course_id'])
            ->where('day', $validated['day'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'يوجد تعارض في الجدول الزمني لهذا اليوم والوقت'
            ], 422);
        }

        $schedule = Schedule::create($validated);
        
        return response()->json([
            'message' => 'تم إنشاء الجدول الزمني بنجاح',
            'data' => $schedule->load(['course.teacher', 'course'])
        ], 201);
    }

    /**
     * تحديث جدول زمني
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'sometimes|required|exists:courses,id',
            'day' => 'sometimes|required|in:السبت,الأحد,الإثنين,الثلاثاء,الأربعاء,الخميس,الجمعة',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من عدم وجود تعارض في الجدول الزمني (استثناء الجدول الحالي)
        if (isset($validated['course_id']) || isset($validated['day']) || isset($validated['start_time']) || isset($validated['end_time'])) {
            $courseId = $validated['course_id'] ?? $schedule->course_id;
            $day = $validated['day'] ?? $schedule->day;
            $startTime = $validated['start_time'] ?? $schedule->start_time;
            $endTime = $validated['end_time'] ?? $schedule->end_time;

            $conflict = Schedule::where('id', '!=', $id)
                ->where('course_id', $courseId)
                ->where('day', $day)
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime])
                          ->orWhereBetween('end_time', [$startTime, $endTime])
                          ->orWhere(function($q) use ($startTime, $endTime) {
                              $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                          });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'يوجد تعارض في الجدول الزمني لهذا اليوم والوقت'
                ], 422);
            }
        }

        $schedule->update($validated);
        
        return response()->json([
            'message' => 'تم تحديث الجدول الزمني بنجاح',
            'data' => $schedule->load(['course.teacher', 'course'])
        ]);
    }

    /**
     * حذف جدول زمني
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        
        return response()->json([
            'message' => 'تم حذف الجدول الزمني بنجاح'
        ], 200);
    }

    /**
     * عرض الجداول الزمنية لدورة معينة
     */
    public function getByCourse($courseId)
    {
        $course = Course::findOrFail($courseId);
        $schedules = Schedule::where('course_id', $courseId)
            ->with(['course.teacher'])
            ->orderByRaw("
                CASE day
                    WHEN 'السبت' THEN 1
                    WHEN 'الأحد' THEN 2
                    WHEN 'الإثنين' THEN 3
                    WHEN 'الثلاثاء' THEN 4
                    WHEN 'الأربعاء' THEN 5
                    WHEN 'الخميس' THEN 6
                    WHEN 'الجمعة' THEN 7
                END
            ")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'course' => $course,
            'schedules' => $schedules
        ]);
    }

    /**
     * عرض الجداول الزمنية لأستاذ معين
     */
    public function getByTeacher($teacherId)
    {
        $schedules = Schedule::whereHas('course', function($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->with(['course.teacher', 'course'])
            ->orderByRaw("
                CASE day
                    WHEN 'السبت' THEN 1
                    WHEN 'الأحد' THEN 2
                    WHEN 'الإثنين' THEN 3
                    WHEN 'الثلاثاء' THEN 4
                    WHEN 'الأربعاء' THEN 5
                    WHEN 'الخميس' THEN 6
                    WHEN 'الجمعة' THEN 7
                END
            ")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'teacher_id' => $teacherId,
            'schedules' => $schedules,
            'count' => $schedules->count()
        ]);
    }

    /**
     * عرض الجداول الزمنية لمستوى معين
     */
    public function getByLevel($level)
    {
        $schedules = Schedule::whereHas('course', function($query) use ($level) {
                $query->where('level', $level);
            })
            ->with(['course.teacher', 'course'])
            ->orderByRaw("
                CASE day
                    WHEN 'السبت' THEN 1
                    WHEN 'الأحد' THEN 2
                    WHEN 'الإثنين' THEN 3
                    WHEN 'الثلاثاء' THEN 4
                    WHEN 'الأربعاء' THEN 5
                    WHEN 'الخميس' THEN 6
                    WHEN 'الجمعة' THEN 7
                END
            ")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'level' => $level,
            'schedules' => $schedules,
            'count' => $schedules->count()
        ]);
    }

    /**
     * عرض الجداول الزمنية ليوم معين
     */
    public function getByDay($day)
    {
        $validDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
        
        if (!in_array($day, $validDays)) {
            return response()->json([
                'message' => 'اسم اليوم غير صحيح'
            ], 422);
        }

        $schedules = Schedule::where('day', $day)
            ->with(['course.teacher', 'course'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'day' => $day,
            'schedules' => $schedules,
            'count' => $schedules->count()
        ]);
    }
}

