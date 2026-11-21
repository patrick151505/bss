<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** The signed-in user's own notifications (top-bar bell). */
class NotificationController extends Controller
{
    // Open one: mark it read, then go where it points.
    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return redirect($n->data['url'] ?? route('root'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    // Light poll for the bell badge: unread count + the newest few (newest first).
    public function unread(Request $request)
    {
        $items = $request->user()->unreadNotifications()->latest()->limit(5)->get()
            ->map(fn($n) => [
                'id'      => $n->id,
                'title'   => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'ago'     => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'items' => $items,
        ])->header('Cache-Control', 'no-store');
    }
}
