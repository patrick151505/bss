@extends('layouts.vertical', [
    'title'         => 'Inventory Items',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'Track stock levels for medicine, materials, and relief goods.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

@php
    $lowStockItems = \App\Models\InventoryItem::active()
        ->whereRaw('stock <= low_stock_threshold')
        ->orderBy('stock')
        ->get();
@endphp

@if($lowStockItems->isNotEmpty())
<div class="mb-5 rounded-xl border border-danger/30 bg-danger/5 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-danger/20">
        <div class="flex items-center gap-2 text-danger font-semibold text-sm">
            <i class="mgc_alert_line text-base"></i>
            {{ $lowStockItems->count() }} item{{ $lowStockItems->count() > 1 ? 's' : '' }} need restocking
        </div>
        <button onclick="this.closest('.mb-5').classList.add('hidden')"
                class="text-danger/60 hover:text-danger transition text-xs flex items-center gap-1">
            <i class="mgc_close_line"></i> Dismiss
        </button>
    </div>
    <div class="divide-y divide-danger/10">
        @foreach($lowStockItems as $ls)
        <div class="flex items-center justify-between px-5 py-2.5 hover:bg-danger/5">
            <div class="flex items-center gap-2.5">
                @if($ls->stock == 0)
                    <span class="w-2 h-2 rounded-full bg-danger inline-block shrink-0"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-warning inline-block shrink-0"></span>
                @endif
                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $ls->name }}</span>
                <span class="text-xs text-gray-400">{{ $ls->category->name ?? '' }}</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold {{ $ls->stock == 0 ? 'text-danger' : 'text-warning' }}">
                    {{ $ls->stock == 0 ? 'Out of stock' : $ls->stock . ' ' . $ls->unit . ' left' }}
                </span>
                <span class="text-xs text-gray-400">threshold: {{ $ls->low_stock_threshold }}</span>
                <a href="{{ route('inventory.items.edit', $ls) }}"
                   class="text-xs text-primary hover:underline flex items-center gap-0.5">
                    Stock in <i class="mgc_arrow_right_line text-xs"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm text-gray-400">
            
        </p>
    </div>
    <a href="{{ route('inventory.items.create') }}" class="btn bg-primary text-white gap-2">
        <i class="mgc_add_line text-base"></i> Add Item
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="flex gap-3 mb-5 flex-wrap">
    <select name="category" class="form-select w-44" onchange="this.form.submit()">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    </select>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item…"
           class="form-input w-56">
    <button class="btn bg-primary text-white">Search</button>
    @if(request()->hasAny(['category','search']))
        <a href="{{ route('inventory.items.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white text-sm">Clear</a>
    @endif
</form>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Items List</h5>
    </div>
    <div class="overflow-x-auto">
        <table class="table min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Category</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Stock</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Unit</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="{{ $item->isLowStock() && $item->stock > 0 ? 'bg-danger/25' : ($item->stock == 0 ? 'bg-danger/5' : '') }}">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($item->image)
                            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}"
                                 class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shrink-0">
                            @else
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                <i class="mgc_pic_line text-gray-300 dark:text-gray-500"></i>
                            </div>
                            @endif
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $item->name }}</p>
                                @if($item->sku)
                                <p class="text-xs font-mono text-gray-400">{{ $item->sku }}</p>
                                @endif
                                @if($item->description)
                                <p class="text-xs text-gray-400">{{ Str::limit($item->description, 50) }}</p>
                                @endif
                                @if($item->requires_approval)
                                <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-0.5">
                                    <span class="w-1.5 h-1.5 inline-block bg-yellow-400 rounded-full"></span>Requires approval
                                </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $item->category->name ?? '—' }}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($item->stock == 0)
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Out of Stock
                            </span>
                        @elseif($item->isLowStock())
                            <span class="font-semibold text-danger">{{ $item->stock }}</span>
                        @else
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format($item->stock) }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center text-sm text-gray-500">{{ $item->unit }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($item->is_active)
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white">
                            <i class="mgc_edit_line"></i> Manage
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">
                        <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>
                        No items found. <a href="{{ route('inventory.items.create') }}" class="text-primary underline">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
    <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700">
        {{ $items->links() }}
    </div>
    @endif
</div>

@endsection
