@extends('layouts.vertical', [
    'title'         => 'Inventory Categories',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'Categories',
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
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Categories</h2>
        <p class="text-sm text-gray-400">Manage inventory categories (medicine, relief goods, materials, etc.)</p>
    </div>
    <a href="{{ route('inventory.categories.create') }}" class="btn bg-primary text-white gap-2">
        <i class="mgc_add_line text-base"></i> Add Category
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Categories List</h5>
    </div>
    <div class="overflow-x-auto">
        <table class="table min-w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Description</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Items</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                @php
                    $catColor = $cat->color ?? 'primary';
                    $isHex    = str_starts_with($catColor, '#');
                    $swatchBg = $isHex ? "background:${catColor}26" : '';
                    $swatchFg = $isHex ? "color:${catColor}"         : '';
                    $swatchCls = $isHex ? '' : "bg-{$catColor}/10";
                    $iconCls   = $isHex ? '' : "text-{$catColor}";
                @endphp
                <tr>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $swatchCls }} flex items-center justify-center shrink-0"
                                 @if($isHex) style="{{ $swatchBg }}" @endif>
                                <i class="{{ $cat->icon ?? 'mgc_box_line' }} {{ $iconCls }} text-lg"
                                   @if($isHex) style="{{ $swatchFg }}" @endif></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $cat->name }}</p>
                                <p class="text-xs text-gray-400">{{ $cat->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500">{{ Str::limit($cat->description, 60) ?: '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="badge bg-primary/10 text-primary text-xs px-2 py-1 rounded-full">{{ $cat->items_count }}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($cat->is_active)
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
                        <a href="{{ route('inventory.categories.edit', $cat) }}" class="btn btn-sm bg-primary text-white">
                            <i class="mgc_edit_line"></i>
                        </a>
                        <form action="{{ route('inventory.categories.destroy', $cat) }}" method="POST" class="inline"
                              id="delete-cat-{{ $cat->id }}">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-sm btn-soft-danger"
                                    onclick="swalDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')">
                                <i class="mgc_delete_line"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-gray-400">
                        <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>
                        No categories yet. <a href="{{ route('inventory.categories.create') }}" class="text-primary underline">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function swalDeleteCategory(id, name) {
    Swal.fire({
        title: 'Delete Category?',
        text: `"${name}" will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#fa5c7c',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('delete-cat-' + id).submit();
    });
}
</script>
@endpush
