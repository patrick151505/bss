<?php

namespace App\Http\Controllers;

use App\Models\AccountableOfficer;
use Illuminate\Http\Request;

class AccountableOfficerController extends Controller
{
    public function index()
    {
        $officers = AccountableOfficer::orderBy('name')->get();
        return view('budget.officers.index', compact('officers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'position'            => 'nullable|string|max:255',
            'fidelity_bond_amount'=> 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        AccountableOfficer::create($data);

        return back()->with('success', 'Accountable officer added.');
    }

    public function update(Request $request, AccountableOfficer $officer)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'position'            => 'nullable|string|max:255',
            'fidelity_bond_amount'=> 'nullable|numeric|min:0',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string',
        ]);

        $officer->update($data);

        return back()->with('success', 'Officer updated.');
    }

    public function destroy(AccountableOfficer $officer)
    {
        if ($officer->cashAdvances()->exists()) {
            return back()->with('error', 'Cannot delete officer with existing cash advances.');
        }

        $officer->delete();
        return back()->with('success', 'Officer removed.');
    }
}
