@extends('layouts.vertical', [
    'title'         => 'Citizen Fields',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Choose which citizen details are required and which must be unique.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@php
    use App\Support\CitizenFieldRules as Rules;
    $canEdit  = auth()->user()->can('settings.edit');
    $required = old('required', $config['required']);
    $unique   = old('unique', $config['unique']);
    $groups   = collect(Rules::FIELDS)->map(fn ($f, $k) => ['name' => $k, 'label' => $f[0], 'group' => $f[1], 'canUnique' => $f[3], 'minor' => $f[4]])->groupBy('group');
@endphp

@section('content')

<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

        @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
            <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-success font-medium">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm font-medium text-danger">{{ $errors->first() }}</p>
        </div>
        @endif

        <div class="card mb-6 p-5 text-sm text-gray-600 dark:text-gray-300">
            <p>These rules apply when staff <strong>add a citizen</strong>, <strong>edit a citizen</strong> or <strong>register a minor</strong>. Required fields get a red <span class="text-danger font-semibold">*</span> on the form and the record cannot be saved while they are empty.</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Records already saved are not changed. They only need the missing details the next time someone edits them.</p>
        </div>

        <form action="{{ route('settings.citizen-fields.update') }}" method="POST">
            @csrf
            <fieldset @unless($canEdit) disabled @endunless>

            {{-- Always required --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_lock_line me-2 text-primary"></i>Always required</h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Every citizen record needs these, so they cannot be turned off.</p>
                </div>
                <div class="p-5 flex flex-wrap gap-2">
                    @foreach(Rules::LOCKED as $label)
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        <i class="mgc_lock_line"></i>{{ $label }}
                    </span>
                    @endforeach
                </div>
            </div>

            {{-- Configurable --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_list_check_line me-2 text-primary"></i>Other fields</h5>
                </div>
                <div class="overflow-x-auto">
                    <table class="table min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Field</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-32">Required</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-40">Must be unique</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($groups as $group => $fields)
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="3" class="px-6 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $group }}</td>
                            </tr>
                                @foreach($fields as $f)
                                <tr>
                                    <td class="px-6 py-3 text-gray-800 dark:text-gray-200">
                                        {{ $f['label'] }}
                                        @unless($f['minor'])
                                        <span class="block text-xs text-gray-400">Not on the Register Minor form</span>
                                        @endunless
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <input type="checkbox" name="required[]" value="{{ $f['name'] }}" class="form-switch text-primary"
                                               aria-label="{{ $f['label'] }} required" @checked(in_array($f['name'], $required))>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if($f['canUnique'])
                                            <input type="checkbox" name="unique[]" value="{{ $f['name'] }}" class="form-switch text-primary"
                                                   aria-label="{{ $f['label'] }} must be unique" @checked(in_array($f['name'], $unique))>
                                            @if(($repeats[$f['name']] ?? 0) > 0)
                                            <span class="block text-xs text-warning mt-1">{{ number_format($repeats[$f['name']]) }} {{ $f['name'] === 'email' ? 'emails' : 'numbers' }} already used by 2+ citizens</span>
                                            @endif
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p><strong>Must be unique</strong>: no two citizens may have the same value. Archived citizens and walk-ins count too. The message names the citizen who already has it.</p>
                    <p>Family members often share one phone or email, so leave these off unless each resident should have their own. Records that already share a value can still be edited, as long as that value is not changed.</p>
                </div>
            </div>

            {{-- Duplicate check --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h5 class="card-title"><i class="mgc_user_3_line me-2 text-primary"></i>Duplicate person check</h5>
                </div>
                <div class="p-5 text-sm text-gray-600 dark:text-gray-300 space-y-3">
                    <p>A citizen with the same <strong>first name, last name and birthday</strong> as an existing record cannot be saved. Close matches (a typo, the same name with a different birthday) show a warning that staff must confirm.</p>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="dup_middle_name" value="1" class="form-switch text-primary mt-0.5"
                               @checked(old('dup_middle_name', $config['dup_middle_name']))>
                        <span>
                            <span class="font-medium text-gray-800 dark:text-gray-100">Middle name must also match</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Turn on when different people in the barangay share a full name and birthday. A different middle name then only shows a warning instead of blocking. A blank middle name or just an initial (e.g. "D.") still counts as a match.</span>
                        </span>
                    </label>
                </div>
            </div>

            @if($canEdit)
            <div class="flex justify-end">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_save_line me-1"></i> Save Citizen Fields
                </button>
            </div>
            @endif

            </fieldset>
        </form>
    </div>
</div>

@endsection
