<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        // Sayfa görüntülenince okunmamışları okundu işaretle
        $request->user()->unreadNotifications->markAsRead();

        return view('panel.notifications.index', compact('notifications'));
    }
}
