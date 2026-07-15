<?php

namespace App\Http\Controllers;

use App\Models\BudgetSupplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetSupplierController extends Controller
{
    public function index()
    {
        $suppliers = BudgetSupplier::orderBy('name')->paginate(25);
        return view('budget.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'tin'              => 'nullable|string|max:50|unique:eb_budget_suppliers,tin',
            'address'          => 'nullable|string|max:500',
            'zip_code'         => 'nullable|string|max:10',
            'line_of_business' => 'nullable|string|max:255',
            'business_type'    => 'required|in:individual,corporation,na',
            'vat_type'         => 'required|in:vat,non_vat,exempt',
            'provides'         => 'required|in:goods,services,both',
            'contact_person'   => 'nullable|string|max:255',
            'contact_no'       => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:255',
        ]);

        if ($data['vat_type'] === 'vat' && empty($data['tin'])) {
            return response()->json(['message' => 'TIN is required for VAT-registered suppliers.'], 422);
        }

        BudgetSupplier::create($data);

        return response()->json(['message' => 'Supplier saved.']);
    }

    public function update(Request $request, BudgetSupplier $supplier)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'tin'              => ['nullable','string','max:50', Rule::unique('eb_budget_suppliers','tin')->ignore($supplier->id)],
            'address'          => 'nullable|string|max:500',
            'zip_code'         => 'nullable|string|max:10',
            'line_of_business' => 'nullable|string|max:255',
            'business_type'    => 'required|in:individual,corporation,na',
            'vat_type'         => 'required|in:vat,non_vat,exempt',
            'provides'         => 'required|in:goods,services,both',
            'contact_person'   => 'nullable|string|max:255',
            'contact_no'       => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:255',
            'is_active'        => 'boolean',
        ]);

        if ($data['vat_type'] === 'vat' && empty($data['tin'])) {
            return response()->json(['message' => 'TIN is required for VAT-registered suppliers.'], 422);
        }

        $supplier->update($data);

        return response()->json(['message' => 'Supplier updated.']);
    }

    public function destroy(BudgetSupplier $supplier)
    {
        if ($supplier->transactions()->exists()) {
            return response()->json(['error' => 'Cannot delete — supplier has existing vouchers.'], 422);
        }
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted.']);
    }

    /** For voucher create dropdown — returns JSON */
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $suppliers = BudgetSupplier::active()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('tin', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'tin', 'address', 'zip_code', 'line_of_business', 'business_type', 'vat_type', 'provides', 'contact_person', 'contact_no']);
        return response()->json($suppliers);
    }
}
