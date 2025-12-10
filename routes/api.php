<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;


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
