<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;

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
