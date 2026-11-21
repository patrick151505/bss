<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DocumentType;
use App\Models\KioskSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Settings → Kiosks (Super Admin only): who can open the kiosk (accounts with
 * kiosk.use), its documents, and the pictures shown on its start screen.
 */
class KioskSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('Super Admin'), 403, 'Only the Super Admin can manage kiosks.');
            return $next($request);
        });
    }

    public function index()
    {
        return view('settings.kiosks', [
            'accounts'   => \App\Models\User::permission('kiosk.use')->with('roles')->orderBy('name')->get()
                                ->reject(fn($u) => $u->hasRole('Super Admin'))->values(),
            'slides'     => KioskSlide::orderBy('sort_order')->orderBy('id')->get(),
            'kioskTypes' => DocumentType::active()->where('allow_kiosk', true)->orderBy('name')->get(['id', 'name', 'fee', 'is_paid']),
        ]);
    }

    // ── Start screen pictures ──────────────────────────────────────────────

    public function slideStore(Request $request)
    {
        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'caption'  => 'nullable|string|max:200',
        ], ['images.*.max' => 'Each picture must be 5 MB or smaller.']);

        $order = (int) KioskSlide::max('sort_order');
        foreach ($request->file('images') as $file) {
            KioskSlide::create([
                'image'      => $file->store('public/kiosk'),
                'caption'    => $request->input('caption'),
                'sort_order' => ++$order,
                'is_active'  => true,
            ]);
        }
        ActivityLog::record('created', 'kiosk_slide', null, count($request->file('images')) . ' kiosk start screen picture(s) added');

        return back()->with('success', 'Picture(s) added to the kiosk start screen.');
    }

    public function slideUpdate(Request $request, KioskSlide $slide)
    {
        $data = $request->validate([
            'caption'   => 'nullable|string|max:200',
            'is_active' => 'nullable|boolean',
            'move'      => 'nullable|in:up,down',
        ]);

        if (!empty($data['move'])) {
            $all = KioskSlide::orderBy('sort_order')->orderBy('id')->get()->values();
            $i = $all->search(fn($s) => $s->id === $slide->id);
            $j = $data['move'] === 'up' ? $i - 1 : $i + 1;
            if ($j >= 0 && $j < $all->count()) {
                $list = $all->all();
                [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
                foreach ($list as $n => $s) $s->update(['sort_order' => $n + 1]);
            }
            return back();
        }

        $slide->update([
            'caption'   => $data['caption'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        return back()->with('success', 'Picture updated.');
    }

    public function slideDestroy(KioskSlide $slide)
    {
        Storage::delete($slide->image);
        $slide->delete();
        ActivityLog::record('deleted', 'kiosk_slide', $slide->id, 'Kiosk start screen picture removed');
        return back()->with('success', 'Picture removed.');
    }

}
