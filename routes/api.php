<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;


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
