<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Official;
use Illuminate\Http\Request;

class OfficialController extends Controller
{
    public function index()
    {
        $officials = Official::orderBy('sort_order')->orderBy('id')->get();

        return view('settings.officials', compact('officials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|max:255',
            'position'    => 'required|max:255',
            'description' => 'nullable|max:500',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $official = Official::create([
            'name'        => $request->name,
            'position'    => $request->position,
            'description' => $request->description,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => 1,
        ]);

        ActivityLog::record('created', 'official', $official->id,
            "Added official: {$official->name} ({$official->position}).");

        return back()->with('success', 'Official added successfully.');
    }

    public function update(Request $request, Official $official)
    {
        $request->validate([
            'name'        => 'required|max:255',
            'position'    => 'required|max:255',
            'description' => 'nullable|max:500',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $official->update([
            'name'        => $request->name,
            'position'    => $request->position,
            'description' => $request->description,
            'sort_order'  => $request->sort_order ?? $official->sort_order,
            'is_active'   => $request->has('is_active') ? $request->is_active : $official->is_active,
        ]);

        ActivityLog::record('updated', 'official', $official->id,
            "Updated official: {$official->name} ({$official->position}).");

        return back()->with('success', 'Official updated successfully.');
    }

    public function destroy(Official $official)
    {
        ActivityLog::record('deleted', 'official', $official->id,
            "Removed official: {$official->name} ({$official->position}).");

        $official->delete();

        return back()->with('success', 'Official removed.');
    }
}
