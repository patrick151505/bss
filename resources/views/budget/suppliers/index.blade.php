@extends('layouts.vertical', [
    'title'         => 'Suppliers / Payees',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Manage supplier and payee records',
])

@section('content')

<div id="toast" class="fixed top-5 right-5 z-[9999] hidden">
    <div id="toast-inner" class="flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px]"></div>
</div>

<div class="grid grid-cols-10 gap-5 items-start">

{{-- LEFT 70% --}}
<div class="col-span-7 space-y-4">

    {{-- Table card --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">
                All Suppliers / Payees
                <span class="ml-2 text-xs text-gray-400 font-normal">({{ $suppliers->total() }})</span>
            </span>
            <button onclick="openAdd()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white !bg-primary hover:!bg-primary/90 transition-colors">
                <i class="mgc_add_line"></i> New Supplier
            </button>
        </div>

        <table class="w-full text-sm table-fixed">
            <colgroup>
                <col style="width:22%">
                <col style="width:22%">
                <col style="width:14%">
                <col style="width:12%">
                <col style="width:16%">
                <col style="width:10%">
                <col style="width:4%">
            </colgroup>
            <thead>
                <tr class="border-b dark:border-gray-700 text-xs text-gray-400 bg-gray-50 dark:bg-gray-700/30">
                    <th class="px-4 py-2.5 text-left">Name</th>
                    <th class="px-4 py-2.5 text-left">Address</th>
                    <th class="px-4 py-2.5 text-left">TIN</th>
                    <th class="px-4 py-2.5 text-left">Type</th>
                    <th class="px-4 py-2.5 text-left">VAT</th>
                    <th class="px-4 py-2.5 text-center">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse($suppliers as $sup)
                @php $vatColors = ['vat'=>'bg-primary/10 text-primary','non_vat'=>'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400','exempt'=>'bg-warning/10 text-warning']; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 group">

                    {{-- Name --}}
                    <td class="px-4 py-2.5 align-top">
                        <div class="font-medium text-gray-800 dark:text-gray-100 truncate">{{ $sup->name }}</div>
                        @if($sup->line_of_business)
                            <div class="text-[10px] text-gray-400 truncate">{{ $sup->line_of_business }}</div>
                        @endif
                        @if($sup->contact_person || $sup->contact_no)
                            <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                {{ implode(' · ', array_filter([$sup->contact_person, $sup->contact_no])) }}
                            </div>
                        @endif
                    </td>

                    {{-- Address --}}
                    <td class="px-4 py-2.5 align-top">
                        @if($sup->address)
                            <div class="text-xs text-gray-500 line-clamp-2">{{ $sup->address }}</div>
                        @endif
                        @if($sup->zip_code)
                            <div class="text-[10px] text-gray-400 mt-0.5">ZIP: {{ $sup->zip_code }}</div>
                        @endif
                        @if(!$sup->address && !$sup->zip_code)
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>

                    {{-- TIN --}}
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-500 align-top">{{ $sup->tin ?: '—' }}</td>

                    {{-- Business type + provides --}}
                    <td class="px-4 py-2.5 text-xs text-gray-500 align-top">
                        <div>{{ \App\Models\BudgetSupplier::BUSINESS_TYPES[$sup->business_type] ?? '—' }}</div>
                        <div class="text-[10px] text-gray-400">{{ \App\Models\BudgetSupplier::PROVIDES[$sup->provides] ?? '' }}</div>
                    </td>

                    {{-- VAT badge --}}
                    <td class="px-4 py-2.5 align-top">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $vatColors[$sup->vat_type] ?? '' }}">
                            {{ \App\Models\BudgetSupplier::VAT_TYPES[$sup->vat_type] ?? $sup->vat_type }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-2.5 text-center align-top">
                        @if($sup->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-success/10 text-success">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-400 dark:bg-gray-700">Inactive</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-2.5 align-top">
                        <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick='openEdit({{ $sup->id }}, @json($sup))' class="p-1 text-gray-400 hover:text-primary rounded" title="Edit">
                                <i class="mgc_edit_line"></i>
                            </button>
                            <button onclick="confirmDelete({{ $sup->id }}, '{{ addslashes($sup->name) }}')" class="p-1 text-gray-400 hover:text-danger rounded" title="Delete">
                                <i class="mgc_delete_line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No suppliers yet. Add your first one.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($suppliers->hasPages())
        <div class="px-5 py-3 border-t dark:border-gray-700">
            {{ $suppliers->links() }}
        </div>
        @endif
    </div>

</div>

{{-- RIGHT 30% --}}
<div class="col-span-3 space-y-3">

    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4 space-y-2 text-xs text-gray-500 dark:text-gray-400">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">About Suppliers</p>
        <div class="flex gap-2"><i class="mgc_user_3_line text-primary shrink-0 mt-0.5"></i><span>Suppliers are payees on disbursement vouchers — contractors, utilities, suppliers of goods/services.</span></div>
        <div class="flex gap-2"><i class="mgc_receipt_line text-primary shrink-0 mt-0.5"></i><span>Saving TIN and address here auto-fills the printed DV form.</span></div>
        <div class="flex gap-2"><i class="mgc_search_line text-primary shrink-0 mt-0.5"></i><span>When creating a voucher, search and select a supplier to pre-fill all fields.</span></div>
        <div class="flex gap-2"><i class="mgc_forbid_line text-primary shrink-0 mt-0.5"></i><span>Suppliers with existing vouchers cannot be deleted — deactivate them instead.</span></div>
    </div>

</div>

</div>

{{-- ADD / EDIT MODAL --}}
<div id="supplier-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="px-5 py-3.5 border-b dark:border-gray-700 flex items-center justify-between">
            <span id="modal-title" class="font-semibold text-sm text-gray-800 dark:text-gray-100">New Supplier</span>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="mgc_close_line text-lg"></i></button>
        </div>
        <form id="supplier-form" class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="supplier-id">

            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Name / Company <span class="text-danger">*</span></label>
                <input type="text" id="f-name" class="form-input w-full text-sm" placeholder="e.g. MERALCO" required>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Business Type <span class="text-danger">*</span></label>
                    <select id="f-business-type" class="form-select w-full text-sm">
                        <option value="individual">Individual</option>
                        <option value="corporation">Corporation</option>
                        <option value="na" selected>N/A</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">VAT Type <span class="text-danger">*</span></label>
                    <select id="f-vat-type" class="form-select w-full text-sm" onchange="onVatChange()">
                        <option value="non_vat" selected>Non-VAT</option>
                        <option value="vat">VAT-Registered</option>
                        <option value="exempt">VAT-Exempt</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Provides <span class="text-danger">*</span></label>
                    <select id="f-provides" class="form-select w-full text-sm">
                        <option value="goods">Goods</option>
                        <option value="services">Services</option>
                        <option value="both">Goods & Services</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                        TIN <span id="tin-required-badge" class="hidden text-danger">*</span>
                        <span id="tin-hint" class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" id="f-tin" class="form-input w-full text-sm" placeholder="000-000-000-000">
                </div>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Line of Business</label>
                    <input type="text" id="f-line-of-business" class="form-input w-full text-sm" placeholder="e.g. Electricity Provider">
                </div>
            </div>

            <div class="grid gap-3">
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Zip Code</label>
                    <input type="text" id="f-zip-code" class="form-input w-full text-sm" placeholder="4027">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Contact Person</label>
                    <input type="text" id="f-contact-person" class="form-input w-full text-sm" placeholder='Contact Person'>
                </div>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Contact No.</label>
                    <input type="text" id="f-contact-no" class="form-input w-full text-sm">
                </div>
            </div>

            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Email</label>
                <input type="email" id="f-email" class="form-input w-full text-sm" placeholder="supplier@example.com">
            </div>

            <div id="active-wrap" class="hidden flex items-center gap-2 pt-1">
                <input type="checkbox" id="f-active" class="rounded">
                <label for="f-active" class="text-xs text-gray-600 dark:text-gray-300">Active (visible in voucher dropdown)</label>
            </div>

            <div class="col-span-2">
                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Address</label>
                <textarea id="f-address" class="form-input w-full text-sm" rows="2" placeholder="Complete address for DV printing"></textarea>
            </div>
        </form>
        <div class="px-5 py-3 border-t dark:border-gray-700 flex justify-end gap-2">
            <button onclick="closeModal()" class="px-4 py-2 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200">Cancel</button>
            <button onclick="saveSupplier()" class="px-4 py-2 text-xs rounded-lg text-white font-semibold !bg-primary hover:!bg-primary/90">Save Supplier</button>
        </div>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const STORE_URL  = '{{ route('budget.suppliers.store') }}';
const CSRF       = '{{ csrf_token() }}';

function showToast(msg, type = 'success') {
    const t   = document.getElementById('toast');
    const inn = document.getElementById('toast-inner');
    inn.className = 'flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px] '
        + (type === 'success' ? 'bg-success text-white' : 'bg-danger text-white');
    inn.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 3500);
}

function onVatChange() {
    const vat  = document.getElementById('f-vat-type').value;
    const badge = document.getElementById('tin-required-badge');
    const hint  = document.getElementById('tin-hint');
    if (vat === 'vat') {
        badge.classList.remove('hidden');
        hint.textContent = '(required for VAT)';
        hint.classList.add('text-danger');
        hint.classList.remove('text-gray-400');
    } else {
        badge.classList.add('hidden');
        hint.textContent = '(optional)';
        hint.classList.remove('text-danger');
        hint.classList.add('text-gray-400');
    }
}

function resetForm() {
    ['f-name','f-tin','f-email','f-address','f-zip-code','f-line-of-business','f-contact-person','f-contact-no'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('f-business-type').value = 'na';
    document.getElementById('f-vat-type').value = 'non_vat';
    document.getElementById('f-provides').value = 'goods';
    onVatChange();
}

function openAdd() {
    document.getElementById('modal-title').textContent = 'New Supplier';
    document.getElementById('supplier-id').value = '';
    resetForm();
    document.getElementById('active-wrap').classList.add('hidden');
    document.getElementById('supplier-modal').classList.remove('hidden');
    setTimeout(() => document.getElementById('f-name').focus(), 50);
}

function openEdit(id, data) {
    document.getElementById('modal-title').textContent = 'Edit Supplier';
    document.getElementById('supplier-id').value = id;
    document.getElementById('f-name').value = data.name || '';
    document.getElementById('f-tin').value = data.tin || '';
    document.getElementById('f-email').value = data.email || '';
    document.getElementById('f-address').value = data.address || '';
    document.getElementById('f-zip-code').value = data.zip_code || '';
    document.getElementById('f-line-of-business').value = data.line_of_business || '';
    document.getElementById('f-business-type').value = data.business_type || 'na';
    document.getElementById('f-vat-type').value = data.vat_type || 'non_vat';
    document.getElementById('f-provides').value = data.provides || 'goods';
    document.getElementById('f-contact-person').value = data.contact_person || '';
    document.getElementById('f-contact-no').value = data.contact_no || '';
    document.getElementById('f-active').checked = !!data.is_active;
    document.getElementById('active-wrap').classList.remove('hidden');
    onVatChange();
    document.getElementById('supplier-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('supplier-modal').classList.add('hidden');
}

async function saveSupplier() {
    const id      = document.getElementById('supplier-id').value;
    const isEdit  = !!id;
    const url     = isEdit ? `/budget/suppliers/${id}` : STORE_URL;
    const method  = isEdit ? 'PUT' : 'POST';
    const payload = {
        name:             document.getElementById('f-name').value.trim(),
        tin:              document.getElementById('f-tin').value.trim(),
        email:            document.getElementById('f-email').value.trim(),
        address:          document.getElementById('f-address').value.trim(),
        zip_code:         document.getElementById('f-zip-code').value.trim(),
        line_of_business: document.getElementById('f-line-of-business').value.trim(),
        business_type:    document.getElementById('f-business-type').value,
        vat_type:         document.getElementById('f-vat-type').value,
        provides:         document.getElementById('f-provides').value,
        contact_person:   document.getElementById('f-contact-person').value.trim(),
        contact_no:       document.getElementById('f-contact-no').value.trim(),
        is_active:        isEdit ? (document.getElementById('f-active').checked ? 1 : 0) : 1,
    };
    if (!payload.name) { showToast('Name is required.', 'error'); return; }
    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) { showToast(data.message || 'Failed.', 'error'); return; }
        closeModal();
        showToast(data.message);
        setTimeout(() => location.reload(), 800);
    } catch(e) { showToast('Network error.', 'error'); }
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete supplier?',
        text: `"${name}" will be permanently removed.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
        },
    }).then(async r => {
        if (!r.isConfirmed) return;
        const res  = await fetch(`/budget/suppliers/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Failed.', 'error'); return; }
        showToast(data.message);
        setTimeout(() => location.reload(), 800);
    });
}

document.getElementById('supplier-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
<style>.swal2-cancel.swal2-styled { background-color: #6c757d !important; }</style>
@endpush
