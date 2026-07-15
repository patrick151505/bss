<?php

namespace App\Http\Controllers;

use App\Models\CitizenIdTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CitizenIdTemplateController extends Controller
{
    public function designer()
    {
        $tpl = CitizenIdTemplate::first() ?? new CitizenIdTemplate();
        return view('citizens.ids.designer', compact('tpl'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'orientation_front' => 'required|in:landscape,portrait',
            'orientation_back'  => 'required|in:landscape,portrait',
            'html_front'        => 'nullable|string',
            'html_back'         => 'nullable|string',
            'css_shared'        => 'nullable|string',
            'js_shared'         => 'nullable|string',
        ]);

        $tpl = CitizenIdTemplate::first() ?? new CitizenIdTemplate();
        $tpl->fill($request->only([
            'orientation_front', 'html_front',
            'orientation_back',  'html_back',
            'css_shared', 'js_shared',
        ]));
        $tpl->save();

        return response()->json(['ok' => true]);
    }

    public function uploadBg(Request $request)
    {
        $request->validate([
            'side' => 'required|in:front,back',
            'bg'   => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $path = $request->file('bg')->store('public/citizen-id-bg');
        $url  = asset(str_replace('public/', 'storage/', $path));

        $tpl = CitizenIdTemplate::firstOrNew([]);
        $tpl->{'bg_' . $request->side} = $path;
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
