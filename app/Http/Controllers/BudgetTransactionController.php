<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetLog;
use App\Models\BudgetProgram;
use App\Models\BudgetTransaction;
use App\Models\BudgetTransactionLine;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetTransactionController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears  = FiscalYear::orderByDesc('year')->get();
        $fy           = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();

        $transactions = $fy
            ? BudgetTransaction::where('fiscal_year_id', $fy->id)
                ->with(['lines.lineItem.program'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(20)
            : collect();

        return view('budget.transactions.index', compact('fiscalYears', 'fy', 'transactions'));
    }

    public function create(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $fy          = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();

        $programs  = BudgetProgram::active()->get();
        $lineItems = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)
                ->orderBy('program_id')->orderBy('object_class')->orderBy('sort_order')
                ->get()
                ->groupBy('program_id')
                ->map(fn($rows) => $rows->groupBy('object_class'))
            : collect();

        $voucherType = $request->get('type', 'DV');
        $voucherNo   = $fy ? BudgetTransaction::generateVoucherNo($voucherType, $fy->id) : '';

        // Pre-encode for JS — avoids Blade @json() choking on nested arrow functions with array literals
        $programsJson  = json_encode($programs->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values());
        $lineItemsJson = json_encode(
            $lineItems->map(fn($byClass) =>
                $byClass->map(fn($items) =>
                    $items->map(fn($i) => [
                        'id'            => $i->id,
                        'name'          => $i->name,
                        'object_code'   => $i->object_code,
                        'object_class'  => $i->object_class,
                        'appropriation' => (float) $i->appropriation,
                        'balance'       => $i->balance(),
                    ])->values()
                )
            )
        );

        return view('budget.transactions.create', compact(
            'fiscalYears', 'fy', 'programs', 'lineItems', 'voucherType', 'voucherNo',
            'programsJson', 'lineItemsJson'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id'       => 'required|exists:eb_fiscal_years,id',
            'voucher_type'         => 'required|in:DV,PCV,Payroll',
            'voucher_no'           => 'required|string|max:50',
            'transaction_date'     => 'required|date',
            'supplier_id'          => 'nullable|exists:eb_budget_suppliers,id',
            'payee'                => 'required|string|max:255',
            'description'          => 'nullable|string|max:1000',
            'mode_of_payment'      => 'required|in:cash,check,bank_transfer',
            'check_no'             => 'nullable|string|max:100',
            'check_date'           => 'nullable|date',
            'bank_name'            => 'nullable|string|max:100',
            'or_number'            => 'nullable|string|max:50',
            'or_date'              => 'nullable|date',
            'amount'               => 'required|numeric|min:0.01',
            'tax_withheld'         => 'nullable|numeric|min:0',
            'tax_type'             => 'nullable|string|max:30',
            'lines'                => 'required|array|min:1',
            'lines.*.line_item_id' => 'required|exists:eb_budget_line_items,id',
            'lines.*.amount'       => 'required|numeric|min:0.01',
            'lines.*.description'  => 'nullable|string|max:500',
        ]);

        // Lines must sum to voucher total
        $linesTotal = collect($data['lines'])->sum('amount');
        if (abs($linesTotal - (float) $data['amount']) > 0.01) {
            return back()
                ->withErrors(['lines' => "Charge lines total (₱" . number_format($linesTotal, 2) . ") must equal voucher amount (₱" . number_format($data['amount'], 2) . ")."])
                ->withInput();
        }

        // Balance check — each charge line must not exceed the line item's remaining balance
        $balanceErrors = [];
        foreach ($data['lines'] as $idx => $line) {
            $lineItem = BudgetLineItem::with('transactionLines.transaction')->find($line['line_item_id']);
            if (!$lineItem) continue;

            $disbursed = $lineItem->transactionLines()
                ->whereHas('transaction', fn($q) => $q->whereIn('status', ['approved', 'paid']))
                ->sum('amount');

            $balance = (float) $lineItem->appropriation - (float) $disbursed;

            if ((float) $line['amount'] > $balance + 0.01) {
                $balanceErrors[] = "Line " . ($idx + 1) . " — \"{$lineItem->name}\": "
                    . "charge ₱" . number_format($line['amount'], 2)
                    . " exceeds available balance ₱" . number_format($balance, 2) . ".";
            }
        }
        if (!empty($balanceErrors)) {
            return back()->withErrors(['lines' => implode(' | ', $balanceErrors)])->withInput();
        }

        // Unique voucher_no within fiscal year
        $exists = BudgetTransaction::where('fiscal_year_id', $data['fiscal_year_id'])
            ->where('voucher_no', $data['voucher_no'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['voucher_no' => 'This voucher number already exists for this fiscal year.'])->withInput();
        }

        DB::transaction(function () use ($data) {
            // Snapshot supplier info at creation time so future edits to the supplier don't affect this voucher
            $supplierId = $data['supplier_id'] ?? null;
            $supplier   = $supplierId ? \App\Models\BudgetSupplier::find($supplierId) : null;

            $tx = BudgetTransaction::create([
                'fiscal_year_id'   => $data['fiscal_year_id'],
                'voucher_type'     => $data['voucher_type'],
                'voucher_no'       => $data['voucher_no'],
                'transaction_date' => $data['transaction_date'],
                'supplier_id'      => $supplierId,
                'payee'            => $supplier?->name ?? $data['payee'],
                'payee_tin'        => $supplier?->tin,
                'payee_address'    => $supplier ? trim(implode(', ', array_filter([$supplier->address, $supplier->zip_code]))) : null,
                'payee_zip_code'   => $supplier?->zip_code,
                'description'      => $data['description'] ?? null,
                'mode_of_payment'  => $data['mode_of_payment'],
                'check_no'         => $data['check_no'] ?? null,
                'check_date'       => $data['check_date'] ?? null,
                'bank_name'        => $data['bank_name'] ?? null,
                'or_number'        => $data['or_number'] ?? null,
                'or_date'          => $data['or_date'] ?? null,
                'amount'           => $data['amount'],
                'tax_withheld'     => $data['tax_withheld'] ?? 0,
                'tax_type'         => $data['tax_type'] ?? null,
                'status'           => 'draft',
                'recorded_by'      => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
                BudgetTransactionLine::create([
                    'transaction_id' => $tx->id,
                    'line_item_id'   => $line['line_item_id'],
                    'amount'         => $line['amount'],
                    'description'    => $line['description'] ?? null,
                ]);
            }

            BudgetLog::record('created', 'budget_transaction', $tx->id,
                "Created {$tx->voucher_type} {$tx->voucher_no} — ₱" . number_format($tx->amount, 2) . " payable to {$tx->payee}"
            );
        });

        return redirect()->route('budget.line-items.index', ['fy' => $data['fiscal_year_id']])
            ->with('success', "Voucher {$data['voucher_no']} saved as draft.");
    }

    public function print(BudgetTransaction $transaction)
    {
        $transaction->load(['lines.lineItem.program', 'recorder', 'fiscalYear', 'supplier']);
        $setting      = \App\Models\BudgetSetting::instance();
        $siteSettings = \App\Models\Setting::instance();

        $brgyLogoB64 = null;
        $muniLogoB64 = null;

        $toBase64 = function (?string $storedPath): ?string {
            if (!$storedPath) return null;
            // DB stores e.g. "public/settings/logo.png" — map to storage_path('app/...')
            $path = storage_path('app/' . $storedPath);
            if (!file_exists($path)) return null;
            $mime = mime_content_type($path);
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        $brgyLogoB64 = $toBase64($siteSettings->logo);
        $muniLogoB64 = $toBase64($siteSettings->municipal_logo);

        return view('budget.transactions.print', compact(
            'transaction', 'setting', 'siteSettings', 'brgyLogoB64', 'muniLogoB64'
        ));
    }

    public function show(BudgetTransaction $transaction)
    {
        $transaction->load(['lines.lineItem.program', 'attachments', 'recorder', 'fiscalYear']);
        return view('budget.transactions.show', compact('transaction'));
    }

    public function updateStatus(Request $request, BudgetTransaction $transaction)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,paid,cancelled',
        ]);

        $old = $transaction->status;
        $transaction->update(['status' => $data['status']]);

        BudgetLog::record('updated', 'budget_transaction', $transaction->id,
            "Status changed from {$old} to {$data['status']} — {$transaction->voucher_no}"
        );

        return back()->with('success', "Voucher status updated to {$data['status']}.");
    }

    public function destroy(BudgetTransaction $transaction)
    {
        if ($transaction->status !== 'draft') {
            return back()->with('error', 'Only draft vouchers can be deleted.');
        }

        $no = $transaction->voucher_no;
        $transaction->delete();

        BudgetLog::record('deleted', 'budget_transaction', 0, "Deleted voucher {$no}");

        return back()->with('success', "Voucher {$no} deleted.");
    }
}
