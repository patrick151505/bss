<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'setting'      => Setting::instance(),
            'paperOptions' => \App\Support\Paper::options(),
        ]);
    }

    /** Settings → Version & Updates: the current version and what changed in each release. */
    public function updates()
    {
        $releases = collect(config('changelog'))->map(fn($r) => $r + [
            'counts' => collect($r['changes'])->countBy('type')->all(),
        ]);
        $modules = $releases->flatMap(fn($r) => array_column($r['changes'], 'module'))->unique()->sort()->values();

        return view('settings.updates', compact('releases', 'modules'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'barangay_name'      => 'nullable|max:255',
            'address'            => 'nullable|max:500',
            'municipality'       => 'nullable|max:255',
            'province'           => 'nullable|max:255',
            'contact'            => 'nullable|max:20',
            'email'              => 'nullable|email|max:255',
            'logo'               => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'municipal_logo'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'captain_name'       => 'nullable|max:255',
            'captain_position'   => 'nullable|max:100',
            'captain_signature'  => 'nullable|image|mimes:png,jpeg,jpg|max:2048',
            'citizen_id_prefix'  => 'nullable|string|max:20|alpha_num',
            'citizen_id_digits'  => 'nullable|integer|min:1|max:10',
            'id_validity'        => 'nullable|in:6m,1y,2y',
            // Print Paper pickers: template ("" = none) + version ("latest" or a version id)
            'blotter_paper_tpl'   => 'nullable|integer|exists:eb_document_templates,id',
            'blotter_paper_ver'   => ['nullable', 'regex:/^(latest|\d+)$/'],
            'inventory_paper_tpl' => 'nullable|integer|exists:eb_document_templates,id',
            'inventory_paper_ver' => ['nullable', 'regex:/^(latest|\d+)$/'],
            'fees_paper_tpl'      => 'nullable|integer|exists:eb_document_templates,id',
            'fees_paper_ver'      => ['nullable', 'regex:/^(latest|\d+)$/'],
            // Optional income classification
            'income_enabled'      => 'nullable|boolean',
            'poverty_threshold'   => 'nullable|numeric|min:1|max:1000000',
        ]);

        $setting = Setting::instance();

        $data = $request->only([
            'barangay_name', 'address', 'municipality', 'province',
            'contact', 'email', 'captain_name', 'captain_position',
            'citizen_id_prefix', 'citizen_id_digits', 'id_validity',
        ]);
        $data['income_enabled']    = $request->boolean('income_enabled');
        $data['poverty_threshold'] = $request->filled('poverty_threshold') ? $request->input('poverty_threshold') : null;

        // Print Paper: template + optional pinned version per printout.
        foreach (['blotter', 'inventory', 'fees'] as $print) {
            [$tplId, $verId] = \App\Support\Paper::parsePick(
                $request->input("{$print}_paper_tpl"),
                $request->input("{$print}_paper_ver")
            );
            $data["{$print}_paper_template_id"] = $tplId;
            $data["{$print}_paper_version_id"]  = $verId;
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->storeAs('public/settings', 'logo.' . $file->getClientOriginalExtension());
            $data['logo'] = $path;
        }

        if ($request->hasFile('municipal_logo')) {
            $file = $request->file('municipal_logo');
            $path = $file->storeAs('public/settings', 'municipal_logo.' . $file->getClientOriginalExtension());
            $data['municipal_logo'] = $path;
        }

        if ($request->hasFile('captain_signature')) {
            $file = $request->file('captain_signature');
            $path = $file->storeAs('public/settings', 'captain_signature.' . $file->getClientOriginalExtension());
            $data['captain_signature'] = $path;
        }

        $changed = [];
        foreach ($data as $field => $newValue) {
            $oldValue = (string) ($setting->$field ?? '');
            $newValue = (string) ($newValue ?? '');
            if ($oldValue !== $newValue) {
                $label           = str_replace('_', ' ', $field);
                $changed[$label] = ['from' => $oldValue ?: '—', 'to' => $newValue ?: '—'];
            }
        }

        $setting->update($data);

        ActivityLog::record(
            'updated',
            'settings',
            1,
            'Barangay settings updated.',
            $changed ? ['changed' => $changed] : []
        );

        return redirect()->route('settings.index')
            ->with('success', 'Barangay settings updated successfully.');
    }
}
