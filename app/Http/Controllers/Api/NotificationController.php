<?php
/*
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // ✅ Import pour les logs
use App\Notifications\GenericNotification;

class NotificationController extends Controller
{
    // ✅ Récupérer toutes les notifications
    public function index()
    {
        $user = auth()->user();
        Log::info('🔍 Chargement des notifications pour l’utilisateur : ' . $user->id);

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type,
                    'data' => $notif->data,
                    'is_read' => $notif->read_at !== null,
                    'created_at' => $notif->created_at
                ];
            });

        Log::info('✅ Notifications récupérées : ', ['count' => $notifications->count()]);
        return $notifications;
    }

    // ✅ Notifications non lues
    public function unread()
    {
        $user = auth()->user();
        Log::info('🔍 Récupération notifications non lues pour : ' . $user->id);

        $notifications = $user->unreadNotifications()->get();

        Log::info('📬 Nombre de notifications non lues : ' . $notifications->count());

        return $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'data' => $notif->data,
                'is_read' => false,
                'created_at' => $notif->created_at
            ];
        });
    }

    // ✅ Marquer une notification comme lue
    public function markAsRead($id)
    {
        $user = auth()->user();
        Log::info("✉️ Tentative de marquage comme lue de la notification ID: {$id}");

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            Log::warning("❌ Notification non trouvée : {$id}");
            return response()->json(['message' => 'Notification introuvable'], 404);
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
            Log::info("✅ Notification marquée comme lue : {$id}");
        } else {
            Log::info("ℹ️ Notification déjà lue : {$id}");
        }

        return response()->json(['message' => 'Notification lue']);
    }

    // ✅ Créer une notification pour un utilisateur
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'type' => 'nullable|string'
        ]);

        Log::info("🆕 Création notification pour user_id={$request->user_id}");

        $user = \App\Models\User::findOrFail($request->user_id);
        $user->notify(new GenericNotification([
            'message' => $request->message,
            'type' => $request->type ?? 'info'
        ]));

        Log::info("✅ Notification envoyée avec succès à l’utilisateur {$user->id}");

        return response()->json(['message' => 'Notification créée']);
    }

    // ✅ Marquer toutes les notifications comme lues
    public function markAllAsRead()
    {
        $user = auth()->user();
        Log::info("🧹 Marquage de toutes les notifications comme lues pour user_id={$user->id}");

        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }

    

    // ✅ Récupérer avec pagination
    public function paginated($perPage = 10)
    {
        $user = auth()->user();
        Log::info("📄 Récupération notifications paginées pour {$user->id}");

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($notifications->through(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'data' => $notif->data,
                'is_read' => $notif->read_at !== null,
                'created_at' => $notif->created_at
            ];
        }));
    } 
}
*/



namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // ✅ Import pour les logs
use App\Notifications\GenericNotification;

class NotificationController extends Controller
{



    public function createNotification($type, $message, $targetId = null)
{
    $notification = Notification::create([
        'type' => $type,
        'message' => $message,
        'target_id' => $targetId, // ici on met l'id de l'objet concerné
        'user_id' => auth()->id(), // utilisateur destinataire
        'is_read' => false,
    ]);

    return response()->json($notification);
}
    // ✅ Récupérer toutes les notifications
    public function index()
    {
        $user = auth()->user();
        Log::info('🔍 Chargement des notifications pour l’utilisateur : ' . $user->id);

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->data['type'] ?? 'info', // type de notification
                    'message' => $notif->data['message'] ?? '',
                    'target_id' => $notif->data['target_id'] ?? null, // ID cible pour la redirection
                    'is_read' => $notif->read_at !== null,
                    'created_at' => $notif->created_at
                ];
            });

        Log::info('✅ Notifications récupérées : ', ['count' => $notifications->count()]);
        return $notifications;
    }

    // ✅ Notifications non lues
    public function unread()
    {
        $user = auth()->user();
        Log::info('🔍 Récupération notifications non lues pour : ' . $user->id);

        $notifications = $user->unreadNotifications()->get();

        Log::info('📬 Nombre de notifications non lues : ' . $notifications->count());

        return $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->data['type'] ?? 'info',
                'message' => $notif->data['message'] ?? '',
                'target_id' => $notif->data['target_id'] ?? null,
                'is_read' => false,
                'created_at' => $notif->created_at
            ];
        });
    }

    // ✅ Marquer une notification comme lue
    public function markAsRead($id)
    {
        $user = auth()->user();
        Log::info("✉️ Tentative de marquage comme lue de la notification ID: {$id}");

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            Log::warning("❌ Notification non trouvée : {$id}");
            return response()->json(['message' => 'Notification introuvable'], 404);
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
            Log::info("✅ Notification marquée comme lue : {$id}");
        } else {
            Log::info("ℹ️ Notification déjà lue : {$id}");
        }

        return response()->json(['message' => 'Notification lue']);
    }

    // ✅ Créer une notification pour un utilisateur avec target_id
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'target_id' => 'nullable|integer'
        ]);

        Log::info("🆕 Création notification pour user_id={$request->user_id}");

        $user = \App\Models\User::findOrFail($request->user_id);
        $user->notify(new GenericNotification([
            'message' => $request->message,
            'type' => $request->type ?? 'info',
            'target_id' => $request->target_id ?? null
        ]));

        Log::info("✅ Notification envoyée avec succès à l’utilisateur {$user->id}");

        return response()->json(['message' => 'Notification créée']);
    }

    // ✅ Marquer toutes les notifications comme lues
    public function markAllAsRead()
    {
        $user = auth()->user();
        Log::info("🧹 Marquage de toutes les notifications comme lues pour user_id={$user->id}");

        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }

    // ✅ Récupérer avec pagination
    public function paginated($perPage = 10)
    {
        $user = auth()->user();
        Log::info("📄 Récupération notifications paginées pour {$user->id}");

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($notifications->through(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->data['type'] ?? 'info',
                'message' => $notif->data['message'] ?? '',
                'target_id' => $notif->data['target_id'] ?? null,
                'is_read' => $notif->read_at !== null,
                'created_at' => $notif->created_at
            ];
        }));
    }
}
