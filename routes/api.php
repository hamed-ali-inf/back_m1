<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatMessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// User route (requires authentication)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Institute Routes
Route::apiResource('institutes', InstituteController::class);

// Department Routes
Route::apiResource('departments', DepartmentController::class);

Route::apiResource('teachers', TeacherController::class);

Route::apiResource('users', UserController::class);

// Student Routes
Route::apiResource('students', StudentController::class);

// Custom Student Routes
Route::get('students/{id}/schedule', [StudentController::class, 'getSchedule']);
Route::get('students/{id}/courses', [StudentController::class, 'getCourses']);
Route::get('students/{id}/announcements', [StudentController::class, 'getAnnouncements']);
Route::get('students/{id}/document-requests', [StudentController::class, 'getDocumentRequests']);
Route::post('students/{id}/request-document', [StudentController::class, 'requestDocument']);
Route::post('students/{id}/send-message/{teacherId}', [StudentController::class, 'sendMessageToTeacher']);

// Schedule Routes
Route::apiResource('schedules', ScheduleController::class);

// Custom Schedule Routes
Route::get('schedules/course/{courseId}', [ScheduleController::class, 'getByCourse']);
Route::get('schedules/teacher/{teacherId}', [ScheduleController::class, 'getByTeacher']);
Route::get('schedules/level/{level}', [ScheduleController::class, 'getByLevel']);
Route::get('schedules/day/{day}', [ScheduleController::class, 'getByDay']);

// Document Request Routes
Route::apiResource('document-requests', DocumentRequestController::class);

// Custom Document Request Routes
Route::post('document-requests/{documentRequest}/approve', [DocumentRequestController::class, 'approve']);
Route::post('document-requests/{documentRequest}/reject', [DocumentRequestController::class, 'reject']);
Route::post('document-requests/{documentRequest}/complete', [DocumentRequestController::class, 'complete']);
Route::post('document-requests/{documentRequest}/cancel', [DocumentRequestController::class, 'cancel']);
Route::get('document-requests-statistics', [DocumentRequestController::class, 'statistics']);

// Announcement Routes
Route::apiResource('announcements', AnnouncementController::class);

// Custom Announcement Routes
Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish']);
Route::post('announcements/{announcement}/archive', [AnnouncementController::class, 'archive']);
Route::post('announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin']);
Route::get('announcements/student/{studentId}', [AnnouncementController::class, 'getForStudent']);
Route::get('announcements-urgent', [AnnouncementController::class, 'getUrgent']);
Route::get('announcements-pinned', [AnnouncementController::class, 'getPinned']);
Route::get('announcements-statistics', [AnnouncementController::class, 'statistics']);
Route::get('announcements-search', [AnnouncementController::class, 'search']);
Route::post('announcements-update-expired', [AnnouncementController::class, 'updateExpired']);

// Chat Routes
Route::apiResource('chats', ChatController::class);

// Custom Chat Routes
Route::post('chats/{chat}/send-message', [ChatController::class, 'sendMessage']);
Route::post('chats/{chat}/upload-file', [ChatController::class, 'uploadFile']);
Route::post('chats/{chat}/add-participant', [ChatController::class, 'addParticipant']);
Route::delete('chats/{chat}/remove-participant', [ChatController::class, 'removeParticipant']);
Route::post('chats/{chat}/mark-as-read', [ChatController::class, 'markAsRead']);
Route::get('chats/{chat}/search-messages', [ChatController::class, 'searchMessages']);
Route::post('chats/create-student-teacher', [ChatController::class, 'createStudentTeacherChat']);
Route::get('chats-statistics', [ChatController::class, 'getStatistics']);

// Chat Messages Routes
Route::get('chats/{chat}/messages', [ChatMessageController::class, 'index']);
Route::get('chat-messages/{message}', [ChatMessageController::class, 'show']);
Route::put('chat-messages/{message}', [ChatMessageController::class, 'update']);
Route::delete('chat-messages/{message}', [ChatMessageController::class, 'destroy']);
Route::post('chat-messages/{message}/reply', [ChatMessageController::class, 'reply']);
Route::post('chat-messages/{message}/mark-as-read', [ChatMessageController::class, 'markAsRead']);
Route::get('chat-messages/{message}/replies', [ChatMessageController::class, 'getReplies']);
Route::get('chat-messages/{message}/download', [ChatMessageController::class, 'downloadFile']);
Route::get('chat-messages-search', [ChatMessageController::class, 'globalSearch']);
Route::get('chats/{chat}/messages-statistics', [ChatMessageController::class, 'getStatistics']);

// Routes للمعلمين - تتطلب المصادقة
Route::middleware('auth:sanctum')->prefix('teacher')->name('teacher.')->group(function () {
    
    // ============================================
    // الجدول الزمني
    // ============================================
    Route::get('/schedule', [TeacherController::class, 'getSchedule'])->name('schedule');
    
    // ============================================
    // المواد الدراسية والدروس
    // ============================================
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [TeacherController::class, 'getCourses'])->name('index');
        Route::get('/{courseId}/files', [TeacherController::class, 'getCourseFiles'])->name('files');
        Route::post('/{courseId}/files', [TeacherController::class, 'uploadFile'])->name('files.upload');
        Route::put('/{courseId}/files/{fileId}', [TeacherController::class, 'updateFile'])->name('files.update');
        Route::delete('/{courseId}/files/{fileId}', [TeacherController::class, 'deleteFile'])->name('files.delete');
    });
    
    // ============================================
    // الدردشة
    // ============================================
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/received', [TeacherController::class, 'getReceivedMessages'])->name('received');
        Route::get('/{messageId}', [TeacherController::class, 'getMessage'])->name('show');
        Route::post('/', [TeacherController::class, 'sendMessage'])->name('send');
        Route::post('/{messageId}/reply', [TeacherController::class, 'sendReply'])->name('reply');
    });
    
    // ============================================
    // الإعلانات
    // ============================================
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [TeacherController::class, 'getAnnouncements'])->name('index');
        Route::post('/', [TeacherController::class, 'sendAnnouncement'])->name('send');
        Route::post('/target', [TeacherController::class, 'sendAnnouncementToTarget'])->name('send.target');
        Route::put('/{announcementId}', [TeacherController::class, 'updateAnnouncement'])->name('update');
        Route::delete('/{announcementId}', [TeacherController::class, 'deleteAnnouncement'])->name('delete');
    });
});
