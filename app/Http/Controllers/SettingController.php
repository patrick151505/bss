<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\Paper;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'setting' => Setting::instance(),
        ]);
    }

    /** Settings → Print Paper: which paper template each printout uses. */
    public function printPaper()
    {
        return view('settings.print-paper', [
            'setting'      => Setting::instance(),
            'paperOptions' => Paper::options(),
            'prints'       => Paper::PRINTS,
        ]);
    }

    public function updatePrintPaper(Request $request)
    {
        $rules = [];
        foreach (array_keys(Paper::PRINTS) as $print) {
            // template ("" = none) + version ("latest" or a version id)
            $rules["{$print}_paper_tpl"] = 'nullable|integer|exists:eb_document_templates,id';
            $rules["{$print}_paper_ver"] = ['nullable', 'regex:/^(latest|\d+)$/'];
        }
        $request->validate($rules);

        $setting = Setting::instance();
        $data    = [];
        $changed = [];
        foreach (Paper::PRINTS as $print => [$label]) {
            [$tplId, $verId] = Paper::parsePick(
                $request->input("{$print}_paper_tpl"),
                $request->input("{$print}_paper_ver")
            );
            $data["{$print}_paper_template_id"] = $tplId;
            $data["{$print}_paper_version_id"]  = $verId;

            $old = [(int) $setting->{"{$print}_paper_template_id"} ?: null, (int) $setting->{"{$print}_paper_version_id"} ?: null];
            if ($old !== [$tplId, $verId]) {
                $changed[$label] = ['from' => $this->paperLabel(...$old), 'to' => $this->paperLabel($tplId, $verId)];
            }
        }

        $setting->update($data);

        if ($changed) {
            ActivityLog::record('updated', 'settings', 1, 'Print paper updated.', ['changed' => $changed]);
        }

        return redirect()->route('settings.print-paper')->with('success', 'Print paper saved.');
    }

    /** "Barangay Letterhead · latest", "Barangay Letterhead · v2" or "None" — for the activity log. */
    private function paperLabel(?int $tplId, ?int $verId): string
    {
        $tpl = $tplId ? \App\Models\DocumentTemplate::find($tplId) : null;
        if (! $tpl) {
            return 'None';
        }
        $ver = $verId ? \App\Models\DocumentTemplateVersion::find($verId) : null;
        return $tpl->name . ' · ' . ($ver ? 'v' . $ver->version : 'latest');
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
