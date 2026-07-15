@extends('layouts.vertical', [
    'title'         => 'Add Category',
    'sub_title'     => 'Inventory',
    'sub_title_url' => route('inventory.releases.index'),
    'tagline'       => 'Add Category',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">New Category</h2>
        <a href="{{ route('inventory.categories.index') }}" class="btn btn-sm rounded-full bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
            <i class="mgc_arrow_left_line"></i> Back
        </a>
    </div>

    <div class="card p-6">
        <form action="{{ route('inventory.categories.store') }}" method="POST">
            @csrf
            @include('inventory.categories._form')
            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('inventory.categories.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</a>
                <button type="submit" class="btn bg-primary text-white">Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
