{{-- One program row. $depth 0 = top-level program, 1 = sub-program. --}}
@php
    $isChild   = $depth > 0;
    $lineItems = $prog->lineItems()->count();
@endphp
<tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ !$prog->is_active ? 'opacity-50' : '' }}">
    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 tabular-nums text-xs {{ $isChild ? 'ps-8' : '' }}">
        {{ $prog->section_label ?? $prog->sort_order }}
    </td>
    <td class="px-4 py-3 {{ $isChild ? 'ps-8' : '' }}">
        <span class="{{ $isChild ? 'text-gray-700 dark:text-gray-300' : 'font-semibold text-gray-800 dark:text-gray-200' }}">
            @if($isChild)<span class="text-gray-300 dark:text-gray-600 me-1">└─</span>@endif
            {{ $prog->name }}
        </span>
        @if($lineItems > 0)
            <span class="ms-2 text-xs text-gray-400">{{ $lineItems }} {{ \Illuminate\Support\Str::plural('line item', $lineItems) }}</span>
        @endif
    </td>
    <td class="px-4 py-3">
        @if($prog->special_fund)
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                <span class="w-1.5 h-1.5 inline-block bg-orange-400 rounded-full"></span>
                {{ \App\Models\BudgetProgram::SPECIAL_FUNDS[$prog->special_fund] }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>
                Discretionary
            </span>
        @endif
    </td>
    <td class="px-4 py-3 text-center">
        @if($prog->is_active)
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>
                Active
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>
                Inactive
            </span>
        @endif
    </td>
    <td class="px-4 py-3 text-right whitespace-nowrap">
        <button class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"
            onclick="openEdit({{ $prog->id }}, @js($prog->name), {{ $prog->parent_id ?? 'null' }}, @js($prog->section_label ?? ''), {{ $prog->sort_order }}, {{ $prog->is_active ? 'true' : 'false' }}, {{ $prog->children()->exists() ? 'true' : 'false' }})">
            <i class="mgc_edit_line"></i>
        </button>
        @if(!$prog->isMandatory())
        <form id="del-prog-{{ $prog->id }}" method="POST" action="{{ route('budget.programs.destroy', $prog) }}" class="inline">
            @csrf @method('DELETE')
            <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"
                onclick="swalDeleteProgram({{ $prog->id }}, @js($prog->name))">
                <i class="mgc_delete_line"></i>
            </button>
        </form>
        @endif
    </td>
</tr>
