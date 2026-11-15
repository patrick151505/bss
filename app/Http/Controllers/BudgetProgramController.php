<?php

namespace App\Http\Controllers;

use App\Models\BudgetProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class BudgetProgramController extends Controller
{
    public function index()
    {
        // Ordered as the budget form prints: each top-level program followed
        // by its sub-programs.
        $programs = BudgetProgram::roots()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $parents = BudgetProgram::roots()->orderBy('sort_order')->get();

        return view('budget.programs.index', compact('programs', 'parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', $this->uniqueName($request->input('parent_id'))],
            'parent_id'     => 'nullable|exists:eb_budget_programs,id',
            'section_label' => 'nullable|string|max:8',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        BudgetProgram::create([
            'name'          => $data['name'],
            'parent_id'     => $data['parent_id'] ?? null,
            'section_label' => $data['section_label'] ?? null,
            'special_fund'  => null,
            'sort_order'    => $data['sort_order'] ?? 99,
            'is_active'     => true,
        ]);

        return back()->with('success', 'Program added.');
    }

    public function update(Request $request, BudgetProgram $program)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', $this->uniqueName($request->input('parent_id'), $program->id)],
            'parent_id'     => 'nullable|exists:eb_budget_programs,id',
            'section_label' => 'nullable|string|max:8',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            if ((int) $parentId === $program->id) {
                return back()->with('error', 'A program cannot be its own parent.');
            }

            // Only two levels are supported, matching the budget form.
            if ($program->children()->exists()) {
                return back()->with('error', "\"{$program->name}\" has sub-programs, so it cannot become a sub-program itself.");
            }

            if (BudgetProgram::whereKey($parentId)->whereNotNull('parent_id')->exists()) {
                return back()->with('error', 'Sub-programs cannot be nested more than one level deep.');
            }
        }

        $program->update([
            'name'          => $data['name'],
            'parent_id'     => $parentId,
            'section_label' => $data['section_label'] ?? null,
            'sort_order'    => $data['sort_order'] ?? $program->sort_order,
            'is_active'     => $data['is_active'] ?? false,
        ]);

        return back()->with('success', 'Program updated.');
    }

    /**
     * Names only need to be unique among siblings — the budget form reuses
     * titles across different parents.
     */
    private function uniqueName($parentId, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('eb_budget_programs', 'name')
            ->where(fn ($q) => $parentId === null || $parentId === ''
                ? $q->whereNull('parent_id')
                : $q->where('parent_id', $parentId));

        return $ignoreId ? $rule->ignore($ignoreId) : $rule;
    }

    public function destroy(BudgetProgram $program)
    {
        if ($program->isMandatory()) {
            return back()->with('error', 'Mandatory fund programs cannot be deleted.');
        }

        if ($program->allocations()->exists()) {
            return back()->with('error', 'Cannot delete a program that has existing allocations.');
        }

        if ($program->lineItems()->exists()) {
            return back()->with('error', 'Cannot delete a program that has existing line items.');
        }

        if ($program->children()->exists()) {
            return back()->with('error', 'Cannot delete a program that has sub-programs. Remove the sub-programs first.');
        }

        $program->delete();
        return back()->with('success', 'Program removed.');
    }
}
