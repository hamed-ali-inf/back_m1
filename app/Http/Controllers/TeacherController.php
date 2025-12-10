<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        return Teacher::all();
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:teachers,email',
        'phone' => 'nullable|string|max:20',
        'user_id' => 'required|exists:users,id',
        'department_id' => 'nullable|exists:departments,id',
        'role' => 'required|in:استاذ عادي,رئيس قسم,رئيس معهد'
    ]);

    $teacher = \App\Models\Teacher::create($validated);

    return response()->json($teacher, 201);
}


    public function show($id)
    {
        return Teacher::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:teachers,email,' . $id,
            'phone' => 'nullable|string',
            'department_id' => 'sometimes|required|exists:departments,id',
            'role' => 'sometimes|required|in:استاذ عادي,رئيس قسم,رئيس معهد'
        ]);

        $teacher->update($request->all());
        return response()->json($teacher);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        return response()->json(null, 204);
    }
}
