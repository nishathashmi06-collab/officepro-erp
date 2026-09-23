<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()
            ->when($request->query('filter') === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Mark as read and follow the notification's link (internal URLs only). */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Only the path/query is used, so a stored link can never leave this app.
        $parts = parse_url((string) ($notification->data['url'] ?? ''));
        $path = $parts['path'] ?? null;

        return $path
            ? redirect()->to(url($path).(isset($parts['query']) ? '?'.$parts['query'] : ''))
            : redirect()->route('notifications.index');
    }

    public function markRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'unread' => $request->user()->unreadNotifications()->count()])
            : back();
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'unread' => 0])
            : back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return back()->with('success', 'Notification removed.');
    }
}
