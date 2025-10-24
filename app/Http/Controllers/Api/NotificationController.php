<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Récupérer toutes les notifications
    public function index() {
        return Notification::where('user_id', Auth::id())
                           ->orderBy('created_at', 'desc')
                           ->get();
    }

    // Marquer une notification comme lue
    public function markAsRead($id) {
        $notif = Notification::where('id', $id)
                             ->where('user_id', Auth::id())
                             ->firstOrFail();
        $notif->is_read = true;
        $notif->save();

        return response()->json(['message' => 'Notification lue']);
    }
    public function unread(Request $request)
{
    $user = $request->user();
    $notifications = $user->notifications()->where('is_read', false)->get();
    return response()->json($notifications);
}

}
