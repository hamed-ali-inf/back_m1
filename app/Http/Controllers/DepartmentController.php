<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json(Department::with('institute')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
        ]);

        $department = Department::create($validated);

        return response()->json($department->load('institute'), 201);
    }

    public function show($id)
    {
        $department = Department::with('institute')->findOrFail($id);
        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
        ]);

        $department->update($validated);

        return response()->json($department->load('institute'));
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        
        return response()->json(['message' => 'Department deleted.']);
    }
}
