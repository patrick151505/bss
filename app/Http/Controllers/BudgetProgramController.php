<?php

namespace App\Http\Controllers;

use App\Models\BudgetProgram;
use Illuminate\Http\Request;

class BudgetProgramController extends Controller
{
    public function index()
    {
        $programs = BudgetProgram::orderBy('sort_order')->orderBy('name')->get();
        return view('budget.programs.index', compact('programs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255|unique:eb_budget_programs,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        BudgetProgram::create([
            'name'         => $data['name'],
            'special_fund' => null,
            'sort_order'   => $data['sort_order'] ?? 99,
            'is_active'    => true,
        ]);

        return back()->with('success', 'Program added.');
    }

    public function update(Request $request, BudgetProgram $program)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255|unique:eb_budget_programs,name,' . $program->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $program->update([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? $program->sort_order,
            'is_active'  => $data['is_active'] ?? $program->is_active,
        ]);

        return back()->with('success', 'Program updated.');
    }

    public function destroy(BudgetProgram $program)
    {
        if ($program->isMandatory()) {
            return back()->with('error', 'Mandatory fund programs cannot be deleted.');
        }

        if ($program->allocations()->exists()) {
            return back()->with('error', 'Cannot delete a program that has existing allocations.');
        }

        $program->delete();
        return back()->with('success', 'Program removed.');
    }
}
