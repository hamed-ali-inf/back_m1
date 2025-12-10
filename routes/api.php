<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherController;

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



