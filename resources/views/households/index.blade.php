@extends('layouts.vertical', [
    'title'         => 'Households',
    'sub_title'     => 'Barangay',
    'sub_title_url' => route('households.index'),
    'tagline'       => 'Household & Family Records',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

{{-- ── KPI Row ─────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_home_3_line text-xl text-primary"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Households</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-households">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
            <i class="mgc_group_2_line text-xl text-success"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Families</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-families">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
            <i class="mgc_user_3_line text-xl text-warning"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total Members</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-members">—</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
            <i class="mgc_chart_bar_line text-xl text-info"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Avg / Household</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100" id="kpi-avg">—</p>
        </div>
    </div>
</div>

{{-- ── Two-column layout ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-5 items-start">

    {{-- ══ LEFT: Household list ══════════════════════════════════════════════ --}}
    <div>
        {{-- Filter bar --}}
        <div class="card mb-4">
            <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">
                    <i class="mgc_search_line text-xs"></i>
                </span>
                <span class="text-sm font-semibold text-primary uppercase tracking-wide">Filter</span>
            </div>
            <div class="p-4">
                <form id="filter-form" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-40">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5">Zone / Area</label>
                        <select name="addressId" class="form-select w-full">
                            <option value="">All Zones</option>
                            @foreach($addresses as $addr)
                                <option value="{{ $addr->id }}">{{ $addr->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5">Search</label>
                        <div class="relative">
                            <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" name="search" class="form-input pl-8 w-full" placeholder="Address, block, lot, name…">
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button type="submit" class="btn bg-primary text-white gap-1.5 text-sm">
                            <i class="mgc_search_line"></i> Search
                        </button>
                        <button type="button" id="btn-reset-filter" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white text-sm px-3">
                            <i class="mgc_close_line"></i>
                        </button>
                        <button type="button" id="btn-add-household" class="btn bg-success text-white gap-1.5 text-sm">
                            <i class="mgc_add_line"></i> New Household
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Household cards --}}
        <div id="household-list" class="space-y-3"></div>

        {{-- Pagination --}}
        <div id="household-pagination" class="hidden flex items-center justify-between mt-4 px-1">
            <p class="text-sm text-gray-400" id="pagination-info"></p>
            <div class="flex gap-1" id="pagination-btns"></div>
        </div>
    </div>

    {{-- ══ RIGHT: Detail panel (sticky) ══════════════════════════════════════ --}}
    <div class="lg:sticky lg:top-4">

        {{-- Empty state placeholder --}}
        <div id="panel-placeholder" class="card p-10 text-center text-gray-400">
            <i class="mgc_home_3_line text-5xl mb-3 block opacity-30"></i>
            <p class="text-sm font-medium">Select a household</p>
            <p class="text-xs mt-1 opacity-70">Click any household card to view its families and members</p>
        </div>

        {{-- Detail panel (hidden until selection) --}}
        <div id="detail-panel" class="hidden card overflow-hidden">

            {{-- Panel header --}}
            <div class="px-5 py-4 bg-primary/5 border-b border-primary/10">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-primary/15 flex items-center justify-center shrink-0">
                            <i class="mgc_home_3_line text-lg text-primary"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-primary/70 font-semibold uppercase tracking-wide" id="panel-hh-id">—</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-snug" id="panel-address">—</p>
                            <p class="text-xs text-info mt-0.5 hidden" id="panel-internal"></p>
                        </div>
                    </div>
                    <button id="btn-close-panel" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0 mt-0.5">
                        <i class="mgc_close_line text-lg"></i>
                    </button>
                </div>

                {{-- Household head --}}
                <div class="mt-3 flex items-center gap-2 p-2 rounded-lg bg-warning/10 border border-warning/20">
                    <div class="w-7 h-7 rounded-full overflow-hidden bg-warning/20 flex items-center justify-center shrink-0" id="panel-head-avatar">
                        <i class="mgc_user_3_line text-warning text-xs"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-warning font-semibold">Household Head</p>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate" id="panel-head-name">—</p>
                    </div>
                    <i class="mgc_star_line text-warning text-sm shrink-0"></i>
                </div>

                {{-- Stats --}}
                <div class="flex gap-4 mt-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-bold text-primary" id="panel-family-count">0</span> Families</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><span class="font-bold text-success" id="panel-member-count">0</span> Total Members</span>
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="px-5 py-2.5 border-b border-gray-100 dark:border-gray-700 flex gap-2">
                <button id="btn-add-family" class="btn btn-sm bg-primary text-white gap-1.5 text-xs">
                    <i class="mgc_add_line"></i> Add Family
                </button>
                <button id="btn-edit-household" class="btn btn-sm bg-dark/25 text-slate-900 hover:bg-dark hover:text-white gap-1.5 text-xs">
                    <i class="mgc_edit_line"></i> Edit Address
                </button>
            </div>

            {{-- Family + member tree --}}
            <div id="family-list" class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[calc(100vh-360px)] overflow-y-auto">
            </div>

        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: New Household — 3-step wizard
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-household" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-hh-backdrop"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg z-10 flex flex-col max-h-[90vh]">

        {{-- Modal header --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="flex items-center justify-between mb-3">
                <h5 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2" id="modal-hh-title">
                    <i class="mgc_home_3_line text-primary"></i> New Household
                </h5>
                <button class="modal-hh-close text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>
            {{-- Step indicator (only for create) --}}
            <div id="step-indicator" class="flex items-center gap-0">
                <div class="flex items-center gap-1.5 flex-1" id="step-item-1">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-primary text-white" id="step-dot-1">1</div>
                    <span class="text-xs font-medium text-primary" id="step-label-1">Select Head</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-600 mx-1" id="step-line-1"></div>
                <div class="flex items-center gap-1.5 flex-1" id="step-item-2">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-gray-200 dark:bg-gray-600 text-gray-400" id="step-dot-2">2</div>
                    <span class="text-xs font-medium text-gray-400" id="step-label-2">Address</span>
                </div>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-600 mx-1" id="step-line-2"></div>
                <div class="flex items-center gap-1.5" id="step-item-3">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-gray-200 dark:bg-gray-600 text-gray-400" id="step-dot-3">3</div>
                    <span class="text-xs font-medium text-gray-400" id="step-label-3">Confirm</span>
                </div>
            </div>
        </div>

        {{-- Step content --}}
        <div class="overflow-y-auto flex-1">

            {{-- STEP 1: Select household head --}}
            <div id="step-1" class="p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Search and select the citizen who will be the <span class="font-semibold text-gray-700 dark:text-gray-200">Household Head</span>.
                </p>
                <div class="relative mb-3">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="step1-search" class="form-input pl-9 w-full" placeholder="Search citizen by name…" autocomplete="off">
                </div>
                <div id="step1-results" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden min-h-[200px]">
                    <div class="p-8 text-center text-gray-400 text-sm">Type a name to search citizens</div>
                </div>
                <div id="step1-selected" class="hidden mt-3 flex items-center gap-3 p-3 rounded-xl bg-success/10 border border-success/30">
                    <div class="w-9 h-9 rounded-full overflow-hidden bg-success/20 flex items-center justify-center shrink-0" id="step1-photo"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate" id="step1-name"></p>
                        <p class="text-xs text-gray-400" id="step1-details"></p>
                    </div>
                    <button type="button" id="btn-change-head" class="text-xs text-primary hover:underline shrink-0">Change</button>
                </div>
                <input type="hidden" id="hh-citizen-id">
            </div>

            {{-- STEP 2: Address --}}
            <div id="step-2" class="hidden p-5 space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Address was auto-filled from the selected citizen. You can adjust it below.
                </p>

                {{-- Zone --}}
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Zone / Area <span class="text-danger">*</span></label>
                    <select id="hh-address" name="addressId" class="form-select w-full">
                        <option value="">Select zone…</option>
                        @foreach($addresses as $addr)
                            <option value="{{ $addr->id }}" data-subd="{{ $addr->is_subd }}">{{ $addr->description }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subdivision: blk/lot/phase --}}
                <div id="hh-fields-subd" class="hidden grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Block</label>
                        <input type="number" name="blk" id="hh-blk" class="form-input w-full" placeholder="e.g. 5">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Lot</label>
                        <input type="number" name="lot" id="hh-lot" class="form-input w-full" placeholder="e.g. 12">
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Phase / Street</label>
                        <input type="text" name="phaseStreet" id="hh-phase" class="form-input w-full" placeholder="Phase 2 / Sampaguita St.">
                    </div>
                </div>

                {{-- Non-subd: no/street --}}
                <div id="hh-fields-nonsubd" class="hidden grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">House No.</label>
                        <input type="text" name="no" id="hh-no" class="form-input w-full" placeholder="e.g. 119">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Street</label>
                        <input type="text" name="phaseStreet2" id="hh-street" class="form-input w-full" placeholder="Rizal St.">
                    </div>
                </div>

                {{-- Others --}}
                <div id="hh-fields-others" class="hidden">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Complete Address</label>
                    <input type="text" name="completeAdress" id="hh-complete" class="form-input w-full" placeholder="Full address…">
                </div>

                {{-- Internal --}}
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">
                        Unit / Interior
                        <span class="text-xs text-gray-400 font-normal ml-1">— optional, for multiple households sharing same address</span>
                    </label>
                    <input type="text" name="internal" id="hh-internal" class="form-input w-full" placeholder="e.g. Bahay sa likod, Unit 2F…">
                </div>
            </div>

            {{-- STEP 3: Confirm --}}
            <div id="step-3" class="hidden p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Review before saving.</p>

                <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                    {{-- Address block --}}
                    <div class="p-4 bg-primary/5 border-b border-primary/10 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-primary/15 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="mgc_home_3_line text-primary"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Household Address</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100" id="confirm-address"></p>
                            <p class="text-xs text-info mt-0.5 hidden" id="confirm-internal"></p>
                        </div>
                    </div>
                    {{-- Head block --}}
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden bg-warning/15 flex items-center justify-center shrink-0" id="confirm-photo">
                            <i class="mgc_user_3_line text-warning"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Household Head</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" id="confirm-name"></p>
                        </div>
                        <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 bg-warning/15 text-warning">
                            <i class="mgc_star_line"></i> HH Head
                        </span>
                    </div>
                </div>

                <div id="hh-error" class="hidden mt-3 p-3 rounded-xl bg-danger/10 border border-danger/30 text-sm text-danger"></div>
            </div>

        </div>

        {{-- Modal footer --}}
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-between shrink-0" id="modal-hh-footer">
            <button type="button" id="btn-step-back" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white gap-1.5 hidden">
                <i class="mgc_arrow_left_line"></i> Back
            </button>
            <div class="ml-auto flex gap-2">
                <button type="button" class="modal-hh-close btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">Cancel</button>
                <button type="button" id="btn-step-next" class="btn bg-primary text-white gap-1.5" disabled>
                    Next <i class="mgc_arrow_right_line"></i>
                </button>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Edit Household Address (simple, no steps)
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-edit-household" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-edit-hh-backdrop"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md z-10">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h5 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_edit_line text-primary"></i> Edit Household Address
            </h5>
            <button class="modal-edit-hh-close text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>
        <form id="edit-hh-form" class="px-6 py-5 space-y-4">
            <input type="hidden" id="edit-hh-id">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Zone / Area <span class="text-danger">*</span></label>
                <select id="edit-hh-address" class="form-select w-full">
                    <option value="">Select zone…</option>
                    @foreach($addresses as $addr)
                        <option value="{{ $addr->id }}" data-subd="{{ $addr->is_subd }}">{{ $addr->description }}</option>
                    @endforeach
                </select>
            </div>
            <div id="edit-hh-fields-subd" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Block</label>
                    <input type="number" id="edit-hh-blk" class="form-input w-full" placeholder="e.g. 5">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Lot</label>
                    <input type="number" id="edit-hh-lot" class="form-input w-full" placeholder="e.g. 12">
                </div>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Phase / Street</label>
                    <input type="text" id="edit-hh-phase" class="form-input w-full">
                </div>
            </div>
            <div id="edit-hh-fields-nonsubd" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">House No.</label>
                    <input type="text" id="edit-hh-no" class="form-input w-full">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Street</label>
                    <input type="text" id="edit-hh-street" class="form-input w-full">
                </div>
            </div>
            <div id="edit-hh-fields-others" class="hidden">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Complete Address</label>
                <input type="text" id="edit-hh-complete" class="form-input w-full">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">
                    Unit / Interior
                    <span class="text-xs text-gray-400 font-normal ml-1">optional</span>
                </label>
                <input type="text" id="edit-hh-internal" class="form-input w-full" placeholder="e.g. Bahay sa likod, Unit 2F…">
            </div>
            <div id="edit-hh-confirm" class="hidden p-3 rounded-xl bg-warning/10 border border-warning/30">
                <p class="text-xs font-semibold text-warning mb-1">Confirm address:</p>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200" id="edit-hh-preview"></p>
                <label class="flex items-center gap-2 mt-2 cursor-pointer">
                    <input type="checkbox" id="edit-hh-confirm-check" class="w-4 h-4 rounded text-primary">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Yes, this is correct</span>
                </label>
            </div>
            <div id="edit-hh-error" class="hidden p-3 rounded-xl bg-danger/10 border border-danger/30 text-sm text-danger"></div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="modal-edit-hh-close btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" id="edit-hh-submit" class="btn bg-primary text-white gap-2">
                    <i class="mgc_check_line"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Citizen Picker (family head / member)
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-citizen-picker" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="modal-picker-backdrop"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-xl z-10 flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <h5 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2" id="picker-title">
                <i class="mgc_user_3_line text-primary"></i> Select Citizen
            </h5>
            <button id="btn-close-picker" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="relative">
                <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="picker-search" class="form-input pl-9 w-full" placeholder="Search by name…">
            </div>
        </div>
        <div class="overflow-y-auto flex-1" id="picker-results"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Relation Picker
══════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-relation" class="fixed inset-0 z-[70] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm z-10">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h5 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i class="mgc_group_line text-primary"></i> Relation to Family Head
            </h5>
            <button id="btn-close-relation" class="text-gray-400 hover:text-gray-600"><i class="mgc_close_line text-xl"></i></button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <p class="text-sm text-gray-500">Adding: <span class="font-semibold text-gray-800 dark:text-gray-100" id="relation-citizen-name"></span></p>
            <input type="hidden" id="relation-citizen-id">
            <input type="hidden" id="relation-family-id">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1.5">Relation <span class="text-danger">*</span></label>
                <select id="relation-select" class="form-select w-full">
                    <option value="">Select relation…</option>
                    @foreach($relations as $rel)
                        <option value="{{ $rel->id }}">{{ $rel->description }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-danger mt-1 hidden" id="relation-error">Please select a relation.</p>
            </div>
            <div class="flex justify-end gap-2">
                <button id="btn-close-relation-2" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">Cancel</button>
                <button id="btn-confirm-relation" class="btn bg-primary text-white gap-2">
                    <i class="mgc_check_line"></i> Add Member
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const ROUTES = {
    kpi:            '{{ route('households.kpi') }}',
    retrieve:       '{{ route('households.retrieve') }}',
    details:        '{{ route('households.details') }}',
    store:          '{{ route('households.store') }}',
    get:            '{{ route('households.get') }}',
    update:         '{{ route('households.update') }}',
    storeFamily:    '{{ route('households.store-family') }}',
    storeMember:    '{{ route('households.store-member') }}',
    removeHead:     '{{ route('households.remove-head') }}',
    removeMember:   '{{ route('households.remove-member') }}',
    setHead:        '{{ route('households.set-household-head') }}',
    filterCitizens: '{{ route('households.filter-citizens') }}',
    citizenAddress: '{{ route('households.citizen-address') }}',
};
const CSRF = '{{ csrf_token() }}';

// ─── State ────────────────────────────────────────────────────────────────────
let currentPage        = 1;
let currentFilter      = { addressId: '', search: '' };
let currentHouseholdId = null;
let pickerMode         = null;
let pickerFamilyId     = null;
let pickerSearch       = '';
let pickerTimer        = null;

// Wizard state
let wizardStep         = 1;
let wizardCitizenId    = null;
let wizardCitizenName  = '';
let wizardCitizenPhoto = null;

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadKpi();
    loadHouseholds();
    bindStaticEvents();
});

// ─── KPI ──────────────────────────────────────────────────────────────────────
function loadKpi() {
    fetch(ROUTES.kpi).then(r => r.json()).then(res => {
        if (!res.success) return;
        document.getElementById('kpi-households').textContent = res.households;
        document.getElementById('kpi-families').textContent   = res.families;
        document.getElementById('kpi-members').textContent    = res.members;
        document.getElementById('kpi-avg').textContent        = res.avg;
    });
}

// ─── Household list ───────────────────────────────────────────────────────────
function loadHouseholds(page = 1) {
    currentPage = page;
    const list  = document.getElementById('household-list');
    list.innerHTML = skeletonCards();

    const params = new URLSearchParams({
        page: page, limit: 8,
        addressId: currentFilter.addressId,
        search:    currentFilter.search,
    });

    fetch(ROUTES.retrieve + '?' + params)
        .then(r => r.json()).then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                list.innerHTML = emptyState(
                    res.data && res.data.length === 0 ? 'No households match your filter.' : 'No households found.'
                );
                renderPagination(0, 1, 8);
                return;
            }
            list.innerHTML = res.data.map(renderHouseholdCard).join('');
            renderPagination(res.total, res.page, res.limit);
            // Re-highlight if panel is open
            if (currentHouseholdId) {
                const active = document.querySelector(`.hh-card[data-id="${currentHouseholdId}"]`);
                if (active) active.classList.add('ring-2', 'ring-primary');
            }
        });
}

function renderHouseholdCard(h) {
    const noFamily = h.family_count === 0;
    return `
    <div class="card hh-card cursor-pointer hover:shadow-md transition-all group border-2 border-transparent"
         data-id="${h.id}" onclick="viewHousehold(${h.id}, this)">
        <div class="p-4">
            <div class="flex items-start gap-3">
                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl ${noFamily ? 'bg-danger/10' : 'bg-primary/10'} flex items-center justify-center shrink-0 mt-0.5">
                    <i class="mgc_home_3_line text-lg ${noFamily ? 'text-danger' : 'text-primary'}"></i>
                </div>

                {{-- Main info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-xs font-mono text-gray-400">${h.formatted_id}</span>
                        ${h.internal ? `<span class="text-xs bg-info/10 text-info rounded px-1.5 py-0.5 font-medium">${h.internal}</span>` : ''}
                    </div>
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-snug truncate">${h.full_address}</p>

                    {{-- Head --}}
                    ${h.head_name
                        ? `<div class="flex items-center gap-1.5 mt-1.5">
                               <div class="w-5 h-5 rounded-full overflow-hidden bg-warning/20 flex items-center justify-center shrink-0">
                                   ${h.head_photo ? `<img src="${h.head_photo}" class="w-full h-full object-cover">` : `<i class="mgc_user_3_line text-warning text-xs"></i>`}
                               </div>
                               <span class="text-xs text-gray-500 dark:text-gray-400 truncate">${h.head_name}</span>
                               <i class="mgc_star_line text-warning text-xs shrink-0" title="Household Head"></i>
                           </div>`
                        : `<p class="text-xs text-warning mt-1.5 flex items-center gap-1"><i class="mgc_alert_line"></i> No head assigned</p>`
                    }
                </div>

                {{-- View btn --}}
                <button class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white text-xs px-3">
                    View <i class="mgc_right_line"></i>
                </button>
            </div>

            {{-- Stats bar --}}
            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                ${noFamily
                    ? `<span class="inline-flex items-center gap-1 text-xs font-medium text-danger bg-danger/10 rounded-full px-2.5 py-1">
                           <i class="mgc_close_circle_line"></i> No family assigned
                       </span>`
                    : `<span class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary bg-primary/10 rounded-full px-2.5 py-1">
                           <i class="mgc_group_2_line"></i> ${h.family_count} ${h.family_count === 1 ? 'Family' : 'Families'}
                       </span>
                       <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-success bg-success/10 rounded-full px-2.5 py-1">
                           <i class="mgc_user_3_line"></i> ${h.member_count} ${h.member_count === 1 ? 'Member' : 'Members'}
                       </span>`
                }
                <span class="ml-auto text-xs text-gray-300 dark:text-gray-600">${h.date_created}</span>
            </div>
        </div>
    </div>`;
}

function renderPagination(total, page, limit) {
    const totalPages = Math.ceil(total / limit);
    const row  = document.getElementById('household-pagination');
    const info = document.getElementById('pagination-info');
    const btns = document.getElementById('pagination-btns');

    if (totalPages <= 1) { row.classList.add('hidden'); return; }
    row.classList.remove('hidden');

    const from = (page - 1) * limit + 1;
    const to   = Math.min(page * limit, total);
    info.textContent = `Showing ${from}–${to} of ${total}`;

    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="loadHouseholds(${i})"
            class="w-8 h-8 rounded-lg text-sm font-medium transition-colors
            ${i === page
                ? 'bg-primary text-white'
                : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
            }">${i}</button>`;
    }
    btns.innerHTML = html;
}

// ─── Detail panel ─────────────────────────────────────────────────────────────
function viewHousehold(id, cardEl) {
    currentHouseholdId = id;

    document.querySelectorAll('.hh-card').forEach(c => c.classList.remove('ring-2', 'ring-primary'));
    cardEl.classList.add('ring-2', 'ring-primary');

    document.getElementById('panel-placeholder').classList.add('hidden');
    const panel = document.getElementById('detail-panel');
    panel.classList.remove('hidden');

    // Loading state
    document.getElementById('panel-hh-id').textContent      = '…';
    document.getElementById('panel-address').textContent    = '…';
    document.getElementById('panel-internal').classList.add('hidden');
    document.getElementById('panel-head-name').textContent  = '—';
    document.getElementById('panel-family-count').textContent = '…';
    document.getElementById('panel-member-count').textContent = '…';
    document.getElementById('family-list').innerHTML = skeletonFamilies();

    fetch(ROUTES.details + '?id=' + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return;
            const d = res.data;

            document.getElementById('panel-hh-id').textContent      = d.household_id_format;
            document.getElementById('panel-address').textContent     = d.full_address;
            document.getElementById('panel-head-name').textContent   = d.head_name || '—';
            document.getElementById('panel-family-count').textContent= d.family_count;
            document.getElementById('panel-member-count').textContent= d.total_members;

            const avatarEl = document.getElementById('panel-head-avatar');
            avatarEl.innerHTML = d.head_photo
                ? `<img src="${d.head_photo}" class="w-full h-full object-cover">`
                : `<i class="mgc_user_3_line text-warning text-xs"></i>`;

            const internalEl = document.getElementById('panel-internal');
            if (d.internal) {
                internalEl.textContent = d.internal;
                internalEl.classList.remove('hidden');
            } else {
                internalEl.classList.add('hidden');
            }

            document.getElementById('family-list').innerHTML =
                d.families.length > 0
                    ? d.families.map(renderFamilyBlock).join('')
                    : `<div class="p-8 text-center text-gray-400">
                           <i class="mgc_group_2_line text-4xl mb-2 block opacity-40"></i>
                           <p class="text-sm">No families yet.</p>
                           <button onclick="document.getElementById('btn-add-family').click()"
                               class="mt-2 text-xs text-primary hover:underline">+ Add first family</button>
                       </div>`;
        });
}

function renderFamilyBlock(f) {
    const isHHHead  = f.is_household_head;
    const headBg    = isHHHead ? 'bg-warning/10 border border-warning/20' : 'bg-gray-50 dark:bg-gray-700/50';
    const headIcon  = isHHHead
        ? `<span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2 py-0.5 bg-warning/15 text-warning shrink-0"><i class="mgc_star_line"></i> HH Head</span>`
        : `<span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2 py-0.5 bg-primary/10 text-primary shrink-0"><i class="mgc_user_3_line"></i> Family Head</span>`;

    const membersHtml = f.members.length > 0
        ? f.members.map((m, idx) => {
            const isLast = idx === f.members.length - 1;
            return `
            <div class="flex items-center gap-2 group/m relative" data-member-id="${m.id}">
                {{-- Tree lines --}}
                <div class="flex flex-col items-center shrink-0" style="width:20px;margin-left:18px;">
                    <div class="w-px flex-1 bg-gray-200 dark:bg-gray-600"></div>
                    <div class="w-3 h-px bg-gray-200 dark:bg-gray-600"></div>
                    ${!isLast ? '' : ''}
                </div>
                <div class="flex items-center gap-2 flex-1 min-w-0 py-1.5 pr-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-7 h-7 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                        ${m.photo ? `<img src="${m.photo}" class="w-full h-full object-cover">` : `<i class="mgc_user_3_line text-gray-400 text-xs"></i>`}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate leading-snug">${m.name}</p>
                        <p class="text-xs text-gray-400 leading-snug">${m.relation} &middot; ${m.gender} &middot; Age ${m.age ?? '—'}</p>
                    </div>
                    <button onclick="removeMember(${m.id}, '${m.name.replace(/'/g,"\\'")}', ${f.family_id})"
                        class="opacity-0 group-hover/m:opacity-100 transition-opacity btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white shrink-0 p-1">
                        <i class="mgc_delete_2_line text-xs"></i>
                    </button>
                </div>
            </div>`;
        }).join('')
        : `<div class="flex items-center gap-2 ml-[38px]">
               <p class="text-xs text-gray-400 italic py-1">No members yet</p>
           </div>`;

    return `
    <div class="px-4 py-3 family-block border-b border-gray-100 dark:border-gray-700 last:border-0"
         data-family-id="${f.family_id}" data-citizen-id="${f.citizen_id}">

        {{-- Family ID row --}}
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-mono text-gray-400">${f.family_id_format}</span>
            <div class="flex items-center gap-1">
                ${!isHHHead
                    ? `<button onclick="setHouseholdHead(${f.family_id}, ${f.citizen_id}, '${f.head_name.replace(/'/g,"\\'")}' )"
                          class="btn btn-sm bg-warning/25 text-warning hover:bg-warning hover:text-white text-xs flex items-center gap-1">
                          <i class="mgc_star_line"></i> Set HH Head
                      </button>`
                    : ''}
                <button onclick="removeFamilyHead(${f.family_id}, ${f.citizen_id}, '${f.head_name.replace(/'/g,"\\'")}', ${f.member_count})"
                    class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white text-xs flex items-center gap-1">
                    <i class="mgc_delete_2_line"></i> Remove
                </button>
            </div>
        </div>

        {{-- Head row --}}
        <div class="flex items-center gap-2.5 p-2 rounded-xl ${headBg} mb-2">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center shrink-0">
                ${f.head_photo ? `<img src="${f.head_photo}" class="w-full h-full object-cover">` : `<i class="mgc_user_3_line text-primary text-sm"></i>`}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate leading-snug">${f.head_name}</p>
            </div>
            ${headIcon}
        </div>

        {{-- Members tree --}}
        <div class="space-y-0 mb-2">
            ${membersHtml}
        </div>

        {{-- Add member --}}
        <button onclick="openCitizenPicker('member', ${f.family_id})"
            class="w-full flex items-center justify-center gap-1 text-xs text-primary border border-dashed border-primary/30 rounded-lg py-1.5 hover:bg-primary/5 transition-colors mt-1">
            <i class="mgc_add_line"></i> Add Member
        </button>
    </div>`;
}

// ─── Wizard: New Household ────────────────────────────────────────────────────
document.getElementById('btn-add-household').addEventListener('click', openWizard);

function openWizard() {
    wizardStep        = 1;
    wizardCitizenId   = null;
    wizardCitizenName = '';
    wizardCitizenPhoto= null;

    // Reset step 1
    document.getElementById('step1-search').value = '';
    document.getElementById('step1-results').innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">Type a name to search citizens</div>';
    document.getElementById('step1-selected').classList.add('hidden');
    document.getElementById('hh-citizen-id').value = '';

    // Reset step 2
    document.getElementById('hh-address').value = '';
    document.getElementById('hh-address').dispatchEvent(new Event('change'));
    document.getElementById('hh-blk').value    = '';
    document.getElementById('hh-lot').value    = '';
    document.getElementById('hh-phase').value  = '';
    document.getElementById('hh-no').value     = '';
    document.getElementById('hh-street').value = '';
    document.getElementById('hh-complete').value = '';
    document.getElementById('hh-internal').value = '';

    // Reset error
    document.getElementById('hh-error').classList.add('hidden');

    // Reset step indicator
    document.getElementById('modal-hh-title').innerHTML = '<i class="mgc_home_3_line text-primary"></i> New Household';
    document.getElementById('step-indicator').classList.remove('hidden');

    setWizardStep(1);
    openModal('modal-household');
}

function setWizardStep(step) {
    wizardStep = step;

    [1,2,3].forEach(n => {
        document.getElementById(`step-${n}`).classList.toggle('hidden', n !== step);
        const dot   = document.getElementById(`step-dot-${n}`);
        const label = document.getElementById(`step-label-${n}`);
        if (n < step) {
            dot.className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-success text-white';
            dot.innerHTML   = '<i class="mgc_check_line text-xs"></i>';
            label.className = 'text-xs font-medium text-success';
        } else if (n === step) {
            dot.className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-primary text-white';
            dot.innerHTML   = String(n);
            label.className = 'text-xs font-medium text-primary';
        } else {
            dot.className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 bg-gray-200 dark:bg-gray-600 text-gray-400';
            dot.innerHTML   = String(n);
            label.className = 'text-xs font-medium text-gray-400';
        }
    });

    const backBtn = document.getElementById('btn-step-back');
    const nextBtn = document.getElementById('btn-step-next');

    backBtn.classList.toggle('hidden', step === 1);
    nextBtn.disabled = (step === 1 && !wizardCitizenId);

    if (step === 3) {
        nextBtn.innerHTML = '<i class="mgc_check_line"></i> Save Household';
        buildConfirmStep();
    } else if (step === 2) {
        nextBtn.innerHTML = 'Next <i class="mgc_arrow_right_line"></i>';
        nextBtn.disabled  = false;
    } else {
        nextBtn.innerHTML = 'Next <i class="mgc_arrow_right_line"></i>';
        nextBtn.disabled  = !wizardCitizenId;
    }
}

function buildConfirmStep() {
    const sel     = document.getElementById('hh-address');
    const isSub   = sel.options[sel.selectedIndex]?.dataset.subd;
    const addrTxt = sel.options[sel.selectedIndex]?.text ?? '';
    let addressStr = '';

    if (isSub === '1') {
        const blk = document.getElementById('hh-blk').value;
        const lot = document.getElementById('hh-lot').value;
        const ps  = document.getElementById('hh-phase').value;
        addressStr = [blk ? 'Blk '+blk : '', lot ? 'Lot '+lot : '', ps, addrTxt].filter(Boolean).join(' ');
    } else if (isSub === '0') {
        addressStr = [document.getElementById('hh-no').value, document.getElementById('hh-street').value, addrTxt].filter(Boolean).join(' ');
    } else {
        addressStr = document.getElementById('hh-complete').value || addrTxt;
    }

    const internal = document.getElementById('hh-internal').value;
    document.getElementById('confirm-address').textContent = addressStr;

    const internalEl = document.getElementById('confirm-internal');
    if (internal) {
        internalEl.textContent = 'Interior/Unit: ' + internal;
        internalEl.classList.remove('hidden');
    } else {
        internalEl.classList.add('hidden');
    }

    document.getElementById('confirm-name').textContent = wizardCitizenName;

    const photoEl = document.getElementById('confirm-photo');
    if (wizardCitizenPhoto) {
        photoEl.innerHTML = `<img src="${wizardCitizenPhoto}" class="w-full h-full object-cover">`;
    } else {
        photoEl.innerHTML = `<i class="mgc_user_3_line text-warning"></i>`;
    }
}

document.getElementById('btn-step-next').addEventListener('click', () => {
    if (wizardStep === 1) {
        if (!wizardCitizenId) return;
        setWizardStep(2);
    } else if (wizardStep === 2) {
        const addressId = document.getElementById('hh-address').value;
        if (!addressId) {
            showWizardError('Please select a zone / area.');
            return;
        }
        document.getElementById('hh-error').classList.add('hidden');
        setWizardStep(3);
    } else if (wizardStep === 3) {
        saveHousehold();
    }
});

document.getElementById('btn-step-back').addEventListener('click', () => {
    if (wizardStep > 1) setWizardStep(wizardStep - 1);
});

function saveHousehold() {
    const btn   = document.getElementById('btn-step-next');
    const sel   = document.getElementById('hh-address');
    const isSub = sel.options[sel.selectedIndex]?.dataset.subd;

    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_4_line animate-spin"></i> Saving…';

    const body = new FormData();
    body.append('_token',    CSRF);
    body.append('citizenId', wizardCitizenId);
    body.append('addressId', sel.value);
    body.append('is_sub',    isSub);
    body.append('internal',  document.getElementById('hh-internal').value);
    body.append('confirmAddress', '1');

    if (isSub === '1') {
        body.append('blk',         document.getElementById('hh-blk').value);
        body.append('lot',         document.getElementById('hh-lot').value);
        body.append('phaseStreet', document.getElementById('hh-phase').value);
    } else if (isSub === '0') {
        body.append('no',           document.getElementById('hh-no').value);
        body.append('phaseStreet2', document.getElementById('hh-street').value);
    } else {
        body.append('completeAdress', document.getElementById('hh-complete').value);
    }

    fetch(ROUTES.store, { method: 'POST', body })
        .then(r => r.json()).then(res => {
            if (!res.success) {
                showWizardError(res.message || 'Failed to save.');
                setWizardStep(3);
                return;
            }
            closeModal('modal-household');
            loadHouseholds(1);
            loadKpi();
            Swal.fire({ icon: 'success', title: 'Household Created!', text: 'The household has been saved.', timer: 2000, showConfirmButton: false });
        })
        .catch(() => showWizardError('An error occurred.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="mgc_check_line"></i> Save Household';
        });
}

function showWizardError(msg) {
    const el = document.getElementById('hh-error');
    el.textContent = msg;
    el.classList.remove('hidden');
}

// ─── Step 1: Citizen search inside wizard ─────────────────────────────────────
let step1Timer = null;
document.getElementById('step1-search').addEventListener('input', function () {
    clearTimeout(step1Timer);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('step1-results').innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">Type at least 2 characters</div>';
        return;
    }
    document.getElementById('step1-results').innerHTML = skeletonPickerRows(3);
    step1Timer = setTimeout(() => loadStep1Citizens(q), 350);
});

function loadStep1Citizens(search, page = 1) {
    fetch(ROUTES.filterCitizens + '?' + new URLSearchParams({ search, page, callback: 'selectCitizenForWizard' }))
        .then(r => r.text()).then(html => {
            document.getElementById('step1-results').innerHTML = html;
        });
}

// Called from citizen-picker partial when inside wizard step 1
function selectCitizenForWizard(id, name, photoUrl, alreadyAssigned) {
    if (alreadyAssigned) {
        Swal.fire({ icon: 'warning', title: 'Already Assigned', text: 'This citizen already belongs to a family. Remove the current assignment first.', confirmButtonColor: '#4361ee' });
        return;
    }
    wizardCitizenId   = id;
    wizardCitizenName = name;
    wizardCitizenPhoto= photoUrl;

    document.getElementById('hh-citizen-id').value = id;

    // Show selected card
    const selEl = document.getElementById('step1-selected');
    document.getElementById('step1-name').textContent = name;
    const photoWrap = document.getElementById('step1-photo');
    photoWrap.innerHTML = photoUrl ? `<img src="${photoUrl}" class="w-full h-full object-cover rounded-full">` : `<i class="mgc_user_3_line text-success"></i>`;
    selEl.classList.remove('hidden');

    // Enable next
    document.getElementById('btn-step-next').disabled = false;

    // Auto-fill address from citizen
    fetch(ROUTES.citizenAddress + '?citizenId=' + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return;
            const d = res.data;
            const addrSel = document.getElementById('hh-address');
            addrSel.value = d.addressId ?? '';
            addrSel.dispatchEvent(new Event('change'));
            setTimeout(() => {
                if (d.is_subd == 1) {
                    document.getElementById('hh-blk').value   = d.blk ?? '';
                    document.getElementById('hh-lot').value   = d.lot ?? '';
                    document.getElementById('hh-phase').value = d.phase ?? '';
                } else if (d.is_subd == 0) {
                    document.getElementById('hh-no').value     = d.no ?? '';
                    document.getElementById('hh-street').value = d.street ?? '';
                } else {
                    document.getElementById('hh-complete').value = d.complete_address ?? '';
                }
                document.getElementById('step1-details').textContent = addrSel.options[addrSel.selectedIndex]?.text ?? '';
            }, 50);
        });
}

document.getElementById('btn-change-head').addEventListener('click', () => {
    wizardCitizenId   = null;
    wizardCitizenName = '';
    wizardCitizenPhoto= null;
    document.getElementById('hh-citizen-id').value = '';
    document.getElementById('step1-selected').classList.add('hidden');
    document.getElementById('step1-search').value = '';
    document.getElementById('step1-results').innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">Type a name to search citizens</div>';
    document.getElementById('btn-step-next').disabled = true;
});

// ─── Address dropdown (wizard step 2) ─────────────────────────────────────────
document.getElementById('hh-address').addEventListener('change', function () {
    const isSub = this.options[this.selectedIndex]?.dataset.subd;
    document.getElementById('hh-fields-subd').classList.toggle('hidden', isSub !== '1');
    document.getElementById('hh-fields-nonsubd').classList.toggle('hidden', isSub !== '0');
    document.getElementById('hh-fields-others').classList.toggle('hidden', isSub !== '2');
});

// ─── Edit household (simple modal) ───────────────────────────────────────────
document.getElementById('btn-edit-household').addEventListener('click', () => {
    if (!currentHouseholdId) return;
    fetch(ROUTES.get + '?id=' + currentHouseholdId)
        .then(r => r.json()).then(res => {
            if (!res.success) return;
            const d = res.data;
            document.getElementById('edit-hh-id').value        = d.id;
            document.getElementById('edit-hh-confirm').classList.add('hidden');
            document.getElementById('edit-hh-confirm-check').checked = false;
            document.getElementById('edit-hh-error').classList.add('hidden');

            const addrSel = document.getElementById('edit-hh-address');
            addrSel.value = d.addressId;
            addrSel.dispatchEvent(new Event('change'));

            setTimeout(() => {
                if (d.is_subd == 1) {
                    document.getElementById('edit-hh-blk').value   = d.blk ?? '';
                    document.getElementById('edit-hh-lot').value   = d.lot ?? '';
                    document.getElementById('edit-hh-phase').value = d.phaseStreet ?? '';
                } else if (d.is_subd == 0) {
                    document.getElementById('edit-hh-no').value     = d.no ?? '';
                    document.getElementById('edit-hh-street').value = d.phaseStreet ?? '';
                } else {
                    document.getElementById('edit-hh-complete').value = d.completeAdress ?? '';
                }
                document.getElementById('edit-hh-internal').value = d.internal ?? '';
            }, 50);

            openModal('modal-edit-household');
        });
});

document.getElementById('edit-hh-address').addEventListener('change', function () {
    const isSub = this.options[this.selectedIndex]?.dataset.subd;
    document.getElementById('edit-hh-fields-subd').classList.toggle('hidden', isSub !== '1');
    document.getElementById('edit-hh-fields-nonsubd').classList.toggle('hidden', isSub !== '0');
    document.getElementById('edit-hh-fields-others').classList.toggle('hidden', isSub !== '2');
});

document.getElementById('edit-hh-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const editId   = document.getElementById('edit-hh-id').value;
    const confirmed= document.getElementById('edit-hh-confirm-check').checked;
    const sel      = document.getElementById('edit-hh-address');
    const isSub    = sel.options[sel.selectedIndex]?.dataset.subd;
    const addrTxt  = sel.options[sel.selectedIndex]?.text ?? '';
    let preview    = '';

    if (isSub === '1') {
        const b = document.getElementById('edit-hh-blk').value;
        const l = document.getElementById('edit-hh-lot').value;
        const p = document.getElementById('edit-hh-phase').value;
        preview = [b ? 'Blk '+b : '', l ? 'Lot '+l : '', p, addrTxt].filter(Boolean).join(' ');
    } else if (isSub === '0') {
        preview = [document.getElementById('edit-hh-no').value, document.getElementById('edit-hh-street').value, addrTxt].filter(Boolean).join(' ');
    } else {
        preview = document.getElementById('edit-hh-complete').value;
    }
    const internal = document.getElementById('edit-hh-internal').value;
    if (internal) preview += ' (' + internal + ')';

    if (!confirmed) {
        document.getElementById('edit-hh-preview').textContent = preview;
        document.getElementById('edit-hh-confirm').classList.remove('hidden');
        return;
    }

    const btn = document.getElementById('edit-hh-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_4_line animate-spin"></i> Saving…';

    const body = new FormData();
    body.append('_token',         CSRF);
    body.append('edit_id',        editId);
    body.append('addressId',      sel.value);
    body.append('is_sub',         isSub);
    body.append('internal',       internal);
    body.append('confirmAddress', '1');

    if (isSub === '1') {
        body.append('blk',         document.getElementById('edit-hh-blk').value);
        body.append('lot',         document.getElementById('edit-hh-lot').value);
        body.append('phaseStreet', document.getElementById('edit-hh-phase').value);
    } else if (isSub === '0') {
        body.append('no',           document.getElementById('edit-hh-no').value);
        body.append('phaseStreet2', document.getElementById('edit-hh-street').value);
    } else {
        body.append('completeAdress', document.getElementById('edit-hh-complete').value);
    }

    fetch(ROUTES.update, { method: 'POST', body })
        .then(r => r.json()).then(res => {
            if (!res.success) {
                document.getElementById('edit-hh-error').textContent = res.message || 'Failed.';
                document.getElementById('edit-hh-error').classList.remove('hidden');
                return;
            }
            closeModal('modal-edit-household');
            loadHouseholds(currentPage);
            Swal.fire({ icon: 'success', title: 'Updated!', timer: 1800, showConfirmButton: false });
            if (currentHouseholdId) {
                const card = document.querySelector(`.hh-card[data-id="${currentHouseholdId}"]`) || document.createElement('div');
                viewHousehold(currentHouseholdId, card);
            }
        })
        .catch(() => {
            document.getElementById('edit-hh-error').textContent = 'An error occurred.';
            document.getElementById('edit-hh-error').classList.remove('hidden');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="mgc_check_line"></i> Save Changes';
        });
});

// ─── Add family head ──────────────────────────────────────────────────────────
document.getElementById('btn-add-family').addEventListener('click', () => {
    if (!currentHouseholdId) return;
    openCitizenPicker('family-head');
});

// ─── Citizen picker (family-head / member) ────────────────────────────────────
function openCitizenPicker(mode, familyId = null) {
    pickerMode     = mode;
    pickerFamilyId = familyId;
    pickerSearch   = '';
    document.getElementById('picker-search').value = '';
    document.getElementById('picker-results').innerHTML = skeletonPickerRows();
    document.getElementById('picker-title').innerHTML =
        mode === 'member'
            ? '<i class="mgc_user_add_line text-success"></i> Add Family Member'
            : '<i class="mgc_user_3_line text-primary"></i> Select Family Head';
    openModal('modal-citizen-picker');
    loadCitizenPicker(1);
}

function loadCitizenPicker(page = 1) {
    const params = new URLSearchParams({ search: pickerSearch, page });
    fetch(ROUTES.filterCitizens + '?' + params)
        .then(r => r.text()).then(html => {
            document.getElementById('picker-results').innerHTML = html;
        });
}

document.getElementById('picker-search').addEventListener('input', function () {
    clearTimeout(pickerTimer);
    pickerSearch = this.value;
    pickerTimer  = setTimeout(() => loadCitizenPicker(1), 350);
});

document.getElementById('btn-close-picker').addEventListener('click', () => closeModal('modal-citizen-picker'));
document.getElementById('modal-picker-backdrop').addEventListener('click', () => closeModal('modal-citizen-picker'));

// Called from partial
function selectCitizen(id, name, photoUrl, alreadyAssigned) {
    if (alreadyAssigned) {
        Swal.fire({ icon: 'warning', title: 'Already Assigned', text: 'This citizen already belongs to a family. Remove the current assignment first.' });
        return;
    }
    if (pickerMode === 'family-head') {
        Swal.fire({
            icon: 'question',
            title: 'Add as Family Head?',
            html: `<p class="text-sm">Add <b>${name}</b> as a new family head in this household?</p>`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Add',
            confirmButtonColor: '#4361ee',
        }).then(result => {
            if (!result.isConfirmed) return;
            closeModal('modal-citizen-picker');
            postJson(ROUTES.storeFamily, { householdId: currentHouseholdId, citizenId: id })
                .then(res => {
                    if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
                    Swal.fire({ icon: 'success', title: 'Done!', text: 'Family added.', timer: 1800, showConfirmButton: false });
                    refreshPanel();
                    loadHouseholds(currentPage);
                    loadKpi();
                });
        });
    } else if (pickerMode === 'member') {
        closeModal('modal-citizen-picker');
        document.getElementById('relation-citizen-id').value        = id;
        document.getElementById('relation-family-id').value         = pickerFamilyId;
        document.getElementById('relation-citizen-name').textContent = name;
        document.getElementById('relation-select').value            = '';
        document.getElementById('relation-error').classList.add('hidden');
        openModal('modal-relation');
    }
}

// ─── Relation confirm ─────────────────────────────────────────────────────────
document.getElementById('btn-confirm-relation').addEventListener('click', () => {
    const relationId = document.getElementById('relation-select').value;
    if (!relationId) { document.getElementById('relation-error').classList.remove('hidden'); return; }
    document.getElementById('relation-error').classList.add('hidden');

    postJson(ROUTES.storeMember, {
        familyId:   document.getElementById('relation-family-id').value,
        citizenId:  document.getElementById('relation-citizen-id').value,
        relationId,
    }).then(res => {
        if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
        closeModal('modal-relation');
        Swal.fire({ icon: 'success', title: 'Member Added!', timer: 1800, showConfirmButton: false });
        refreshPanel();
        loadHouseholds(currentPage);
        loadKpi();
    });
});

['btn-close-relation','btn-close-relation-2'].forEach(id => {
    document.getElementById(id).addEventListener('click', () => closeModal('modal-relation'));
});

// ─── Remove family head ───────────────────────────────────────────────────────
function removeFamilyHead(familyId, citizenId, name, memberCount) {
    if (memberCount > 0) {
        Swal.fire({ icon: 'warning', title: 'Cannot Remove', text: `${name} still has ${memberCount} member(s). Remove all members first.` });
        return;
    }
    Swal.fire({
        icon: 'question', title: 'Remove Family Head?',
        html: `<p class="text-sm">Remove <b>${name}</b>? The family record will be deleted.</p>`,
        showCancelButton: true, confirmButtonText: 'Yes, Remove', confirmButtonColor: '#ef4444',
    }).then(r => {
        if (!r.isConfirmed) return;
        postJson(ROUTES.removeHead, { familyId, citizenId }).then(res => {
            if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
            Swal.fire({ icon: 'success', title: 'Removed', timer: 1600, showConfirmButton: false });
            refreshPanel();
            loadHouseholds(currentPage);
            loadKpi();
        });
    });
}

// ─── Remove member ────────────────────────────────────────────────────────────
function removeMember(memberId, name) {
    Swal.fire({
        icon: 'question', title: 'Remove Member?',
        html: `<p class="text-sm">Remove <b>${name}</b> from this family?</p>`,
        showCancelButton: true, confirmButtonText: 'Yes, Remove', confirmButtonColor: '#ef4444',
    }).then(r => {
        if (!r.isConfirmed) return;
        postJson(ROUTES.removeMember, { memberId }).then(res => {
            if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
            Swal.fire({ icon: 'success', title: 'Removed', timer: 1600, showConfirmButton: false });
            refreshPanel();
            loadHouseholds(currentPage);
            loadKpi();
        });
    });
}

// ─── Set household head ───────────────────────────────────────────────────────
function setHouseholdHead(familyId, citizenId, name) {
    Swal.fire({
        icon: 'question', title: 'Set as Household Head?',
        html: `<p class="text-sm"><b>${name}</b> will become the main household head.</p>`,
        showCancelButton: true, confirmButtonText: 'Yes, Set', confirmButtonColor: '#4361ee',
    }).then(r => {
        if (!r.isConfirmed) return;
        postJson(ROUTES.setHead, { householdId: currentHouseholdId, citizenId }).then(res => {
            if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message }); return; }
            Swal.fire({ icon: 'success', title: 'Updated!', timer: 1600, showConfirmButton: false });
            refreshPanel();
            loadHouseholds(currentPage);
        });
    });
}

// ─── Close panel ─────────────────────────────────────────────────────────────
document.getElementById('btn-close-panel').addEventListener('click', () => {
    document.getElementById('detail-panel').classList.add('hidden');
    document.getElementById('panel-placeholder').classList.remove('hidden');
    document.querySelectorAll('.hh-card').forEach(c => c.classList.remove('ring-2','ring-primary'));
    currentHouseholdId = null;
});

// ─── Filter ───────────────────────────────────────────────────────────────────
document.getElementById('filter-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const data    = new FormData(this);
    currentFilter = { addressId: data.get('addressId') || '', search: data.get('search') || '' };
    loadHouseholds(1);
});
document.getElementById('btn-reset-filter').addEventListener('click', () => {
    document.getElementById('filter-form').reset();
    currentFilter = { addressId: '', search: '' };
    loadHouseholds(1);
});

// ─── Helpers ─────────────────────────────────────────────────────────────────
function refreshPanel() {
    if (!currentHouseholdId) return;
    const card = document.querySelector(`.hh-card[data-id="${currentHouseholdId}"]`) || document.createElement('div');
    viewHousehold(currentHouseholdId, card);
}

function bindStaticEvents() {
    document.querySelectorAll('.modal-hh-close').forEach(b => b.addEventListener('click', () => closeModal('modal-household')));
    document.querySelectorAll('.modal-edit-hh-close').forEach(b => b.addEventListener('click', () => closeModal('modal-edit-household')));
    document.getElementById('modal-hh-backdrop').addEventListener('click', () => closeModal('modal-household'));
    document.getElementById('modal-edit-hh-backdrop').addEventListener('click', () => closeModal('modal-edit-household'));
}

function openModal(id)  { const el = document.getElementById(id); el.classList.remove('hidden'); el.classList.add('flex'); }
function closeModal(id) { const el = document.getElementById(id); el.classList.add('hidden'); el.classList.remove('flex'); }

function postJson(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    }).then(r => r.json());
}

// Expose functions used in dynamic onclick attributes so they survive any bundler scope
window.viewHousehold          = viewHousehold;
window.selectCitizen          = selectCitizen;
window.selectCitizenForWizard = selectCitizenForWizard;
window.loadCitizenPicker      = loadCitizenPicker;
window.loadStep1Citizens      = loadStep1Citizens;
window.openCitizenPicker      = openCitizenPicker;
window.removeFamilyHead       = removeFamilyHead;
window.removeMember           = removeMember;
window.setHouseholdHead       = setHouseholdHead;
window.openWizard             = openWizard;

// ─── Skeleton loaders ─────────────────────────────────────────────────────────
function skeletonCards(n = 4) {
    const c = `<div class="card p-4 animate-pulse">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 shrink-0"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/5"></div>
            </div>
        </div>
        <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full w-24"></div>
            <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full w-20"></div>
        </div>
    </div>`;
    return Array(n).fill(c).join('');
}

function skeletonFamilies(n = 2) {
    const b = `<div class="px-4 py-3 animate-pulse border-b border-gray-100 dark:border-gray-700">
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-2"></div>
        <div class="flex items-center gap-2 p-2 rounded-xl bg-gray-100 dark:bg-gray-700 mb-2">
            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 shrink-0"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded flex-1"></div>
        </div>
        <div class="ml-9 space-y-2">
            <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded w-2/3"></div>
        </div>
    </div>`;
    return Array(n).fill(b).join('');
}

function skeletonPickerRows(n = 5) {
    const r = `<div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 dark:border-gray-700 animate-pulse">
        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
        <div class="flex-1 space-y-1.5">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/5"></div>
            <div class="h-3 bg-gray-100 dark:bg-gray-600 rounded w-3/5"></div>
        </div>
    </div>`;
    return Array(n).fill(r).join('');
}

function emptyState(msg) {
    return `<div class="card py-14 text-center text-gray-400">
        <i class="mgc_home_3_line text-5xl mb-3 block opacity-30"></i>
        <p class="text-sm font-medium">${msg}</p>
        <button onclick="openWizard()" class="mt-3 text-xs text-primary hover:underline">+ Create your first household</button>
    </div>`;
}
</script>
@endpush
