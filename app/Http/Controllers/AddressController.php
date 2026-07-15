<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Address::withCount('citizens')->orderBy('description')->get();
        return view('settings.addresses', compact('addresses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:250|unique:eb_address,description',
            'is_subd'     => 'required|in:0,1,2',
        ]);

        $rules = $this->buildRules($request);

        Address::create([
            'description'    => strtoupper(trim($request->description)),
            'is_subd'        => $request->is_subd,
            'rules_required' => $rules,
        ]);

        return back()->with('success', 'Zone/Address added successfully.');
    }

    public function update(Request $request, Address $address)
    {
        $request->validate([
            'description' => 'required|string|max:250|unique:eb_address,description,' . $address->id,
            'is_subd'     => 'required|in:0,1,2',
        ]);

        $rules = $this->buildRules($request);

        $address->update([
            'description'    => strtoupper(trim($request->description)),
            'is_subd'        => $request->is_subd,
            'rules_required' => $rules,
        ]);

        return back()->with('success', 'Zone/Address updated.');
    }

    public function toggleActive(Address $address)
    {
        $address->update(['is_active' => !$address->is_active]);
        $status = $address->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "\"{$address->description}\" {$status}.");
    }

    private function buildRules(Request $request): string
    {
        if ((int) $request->is_subd !== 1) {
            return '';
        }

        $fields = [];
        if ($request->boolean('rule_blk'))   $fields[] = 'blk';
        if ($request->boolean('rule_lot'))   $fields[] = 'lot';
        if ($request->boolean('rule_phase')) $fields[] = 'phase';

        return implode(',', $fields);
    }
}
