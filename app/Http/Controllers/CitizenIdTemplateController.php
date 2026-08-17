<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\CitizenId;
use App\Models\CitizenIdTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CitizenIdTemplateController extends Controller
{
    public function designer()
    {
        $tpl = CitizenIdTemplate::first() ?? new CitizenIdTemplate();

        // Advanced CSS/JS runs unfiltered on the printed ID — restrict to Super Admin.
        $canAdvanced = auth()->user()?->hasRole('Super Admin') ?? false;

        return view('citizens.ids.designer', compact('tpl', 'canAdvanced'));
    }

    /**
     * Resolve a citizen into the exact placeholder values the printed ID uses,
     * so the designer preview is accurate. Returns the same map for {{tags}}.
     */
    public function previewData(Citizen $citizen)
    {
        $citizen->loadMissing('addressZone');
        return response()->json(CitizenId::placeholderValues($citizen));
    }

    public function save(Request $request)
    {
        $request->validate([
            'orientation_front' => 'required|in:landscape,portrait',
            'orientation_back'  => 'required|in:landscape,portrait',
            'layout_front'      => 'nullable|array',
            'layout_back'       => 'nullable|array',
            'css_shared'        => 'nullable|string',
            'js_shared'         => 'nullable|string',
        ]);

        $layoutFront = $request->input('layout_front', []);
        $layoutBack  = $request->input('layout_back', []);

        $tpl = CitizenIdTemplate::first() ?? new CitizenIdTemplate();
        $tpl->orientation_front = $request->orientation_front;
        $tpl->orientation_back  = $request->orientation_back;

        // Store the editable layout AND its compiled HTML. Print reads the HTML,
        // so what the editor shows is exactly what prints.
        $tpl->layout_front = $layoutFront;
        $tpl->layout_back  = $layoutBack;
        $tpl->html_front   = CitizenIdTemplate::compileLayout($layoutFront);
        $tpl->html_back    = CitizenIdTemplate::compileLayout($layoutBack);

        // Advanced custom CSS/JS is Super-Admin only (it runs unfiltered on print).
        // Other roles can still edit the layout; the stored CSS/JS is left untouched.
        if (auth()->user()?->hasRole('Super Admin')) {
            $tpl->css_shared = $request->input('css_shared', '') ?? '';
            $tpl->js_shared  = $request->input('js_shared', '') ?? '';
        }

        $tpl->save();

        return response()->json(['ok' => true]);
    }

    public function uploadBg(Request $request)
    {
        $request->validate([
            'side' => 'required|in:front,back',
            'bg'   => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $col = 'bg_' . $request->side;
        $tpl = CitizenIdTemplate::firstOrNew([]);

        // Drop the previous background so old uploads don't pile up in storage.
        if ($tpl->$col) {
            Storage::delete($tpl->$col);
        }

        $path = $request->file('bg')->store('public/citizen-id-bg');
        $url  = asset(str_replace('public/', 'storage/', $path));

        $tpl->$col = $path;
        $tpl->save();

        return response()->json(['path' => $path, 'url' => $url]);
    }

    public function removeBg(Request $request)
    {
        $request->validate(['side' => 'required|in:front,back']);

        $tpl = CitizenIdTemplate::first();
        if ($tpl) {
            $col = 'bg_' . $request->side;
            if ($tpl->$col) {
                Storage::delete($tpl->$col);
                $tpl->$col = null;
                $tpl->save();
            }
        }

        return response()->json(['ok' => true]);
    }
}
