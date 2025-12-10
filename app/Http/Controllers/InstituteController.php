<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;

class InstituteController extends Controller
{
    public function index()
    {
        return response()->json(Institute::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $institute = Institute::create($validated);

        return response()->json($institute, 201);
    }

    public function show($id)
    {
        $institute = Institute::findOrFail($id);
        return response()->json($institute);
    }

    public function update(Request $request, $id)
    {
        $institute = Institute::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $institute->update($validated);

        return response()->json($institute);
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);
        $institute->delete();
        
        return response()->json(['message' => 'Institute deleted.']);
    }
}
