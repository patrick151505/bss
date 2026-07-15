<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', ['setting' => Setting::instance()]);
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
            'citizen_id_prefix'  => 'nullable|string|max:20|alpha_num',
            'citizen_id_digits'  => 'nullable|integer|min:1|max:10',
        ]);

        $setting = Setting::instance();

        $data = $request->only([
            'barangay_name', 'address', 'municipality', 'province',
            'contact', 'email', 'captain_name', 'captain_position',
            'citizen_id_prefix', 'citizen_id_digits',
        ]);

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
