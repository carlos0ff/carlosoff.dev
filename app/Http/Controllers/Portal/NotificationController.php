<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends PortalController
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(25);
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return view('portal.notifications.index', ['notifications' => $notifications]);
    }
}
