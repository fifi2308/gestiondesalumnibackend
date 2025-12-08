<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\GenericNotification;
//use Illuminate\Support\Facades\Notification;


class MessageController extends Controller
{
    // 🔹 Récupérer tous les messages reçus
    public function index() {
        return Message::where('receiver_id', Auth::id())
                      ->with('sender:id,name')
                      ->orderBy('created_at', 'desc')
                      ->get();
    }



   /*    public function store(Request $request)
{
    $request->validate([
        'receiver_id' => 'required|integer',
        'contenu' => 'nullable|string',
        'image' => 'nullable|file|image|mimes:jpg,jpeg,png,gif|max:2048',
        'video' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime|max:10240',
    ]);

    $message = new Message();
    $message->sender_id = auth()->id();
    $message->receiver_id = $request->receiver_id;
    $message->contenu = $request->contenu;

    // ✅ Gestion de l'image
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('messages/images', 'public');
        $message->image_path = $path;
    }

    // ✅ Gestion de la vidéo
    if ($request->hasFile('video')) {
        $path = $request->file('video')->store('messages/videos', 'public');
        $message->video_path = $path;
    }

    $message->save();

  
    $receiver = \App\Models\User::find((int)$request->receiver_id);


if ($receiver) {
    $receiver->notify(new GenericNotification([
        'message' => "Vous avez reçu un nouveau message de {$message->sender->name}",
        'type' => 'message',
          'target_id' => $message->sender_id, // 🔹 target_id pour redirection vers conversation
        'data' => [
            'message_id' => $message->id,
            'sender_id' => $message->sender_id,
             'sender_name' => $user->name
        ]
    ]));
}


    // ✅ On ajoute l’URL publique de l’image si elle existe
    if ($message->image_path) {
        $message->image = asset('storage/' . $message->image_path);
    }

    if ($message->video_path) {
        $message->video = asset('storage/' . $message->video_path);
    }

    return response()->json([
        'success' => true,
        'message' => $message,
    ]);
}*/

public function store(Request $request)
{
    $request->validate([
        'receiver_id' => 'required|integer',
        'contenu' => 'nullable|string',
        'image' => 'nullable|file|image|mimes:jpg,jpeg,png,gif|max:2048',
        'video' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime|max:10240',
    ]);

    $sender = auth()->user(); // ✅ Expéditeur actuel

    // Création du message
    $message = new Message();
    $message->sender_id = $sender->id;
    $message->receiver_id = $request->receiver_id;
    $message->contenu = $request->contenu;

    // ✅ Gestion de l'image
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('messages/images', 'public');
        $message->image_path = $path;
    }

    // ✅ Gestion de la vidéo
    if ($request->hasFile('video')) {
        $path = $request->file('video')->store('messages/videos', 'public');
        $message->video_path = $path;
    }

    $message->save();

    // Récupération du destinataire
    $receiver = \App\Models\User::find((int)$request->receiver_id);

    // ✅ Création de la notification
    if ($receiver) {
       $receiver->notify(new GenericNotification([
    'message' => "Vous avez reçu un nouveau message de {$sender->name}",
    'type' => 'message',
    'target_id' => $message->id, // ✅ ID du message pour redirection
    'sender_name' => $sender->name, 
    'data' => [
        'message_id' => $message->id,
        'sender_id' => $sender->id,
        'sender_name' => $sender->name
    ]
]));

    }

    // ✅ Ajout des URLs publiques pour front
    if ($message->image_path) {
        $message->image = asset('storage/' . $message->image_path);
    }

    if ($message->video_path) {
        $message->video = asset('storage/' . $message->video_path);
    }

    return response()->json([
        'success' => true,
        'message' => $message,
    ]);
}




    // 🔹 Récupérer la liste des conversations avec le nom et le dernier message
    public function getConversations() {
        $userId = Auth::id();

        $messages = Message::where('sender_id', $userId)
                            ->orWhere('receiver_id', $userId)
                            ->with(['sender:id,name', 'receiver:id,name'])
                            ->orderBy('created_at', 'desc')
                            ->get();

        $conversations = [];

        foreach ($messages as $msg) {
            $otherUser = $msg->sender_id === $userId ? $msg->receiver : $msg->sender;

            if (!isset($conversations[$otherUser->id])) {
                $conversations[$otherUser->id] = [
                    'user_id' => $otherUser->id,
                    'user_name' => $otherUser->name,
                    'last_message' => $msg->contenu,
                    'updated_at' => $msg->created_at->diffForHumans(),
                ];
            }
        }

        return response()->json(array_values($conversations));
    }

    // 🔹 Marquer un message comme lu
    public function markAsRead($id) {
        $message = Message::where('id', $id)
                          ->where('receiver_id', Auth::id())
                          ->firstOrFail();
        $message->is_read = true;
        $message->save();

        return response()->json(['message' => 'Message marqué comme lu']);
    }

    // 🔹 Récupérer les messages non lus
    public function unread(Request $request)
    {
        $user = $request->user();
        $messages = $user->messages()->where('is_read', false)->get();
        return response()->json($messages);
    }

    // 🔹 Récupérer la conversation complète entre deux utilisateurs ou les messages non lus
   public function getMessagesWithUser($userId)
{
    $currentUserId = auth()->id();

    if ($userId === 'unread') {
        $messages = Message::where('receiver_id', $currentUserId)
                            ->where('is_read', false)
                            ->with('sender:id,name')
                            ->orderBy('created_at')
                            ->get();
    } else {
        $otherUserId = (int) $userId;
        $messages = Message::where(function ($q) use ($currentUserId, $otherUserId) {
                $q->where('sender_id', $currentUserId)
                  ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($currentUserId, $otherUserId) {
                $q->where('sender_id', $otherUserId)
                  ->where('receiver_id', $currentUserId);
            })
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get();
    }

    // ✅ On ajoute les URLs complètes pour toutes les images et vidéos
    $messages->transform(function ($msg) {
        $msg->image = $msg->image_path ? asset('storage/' . $msg->image_path) : null;
        $msg->video = $msg->video_path ? asset('storage/' . $msg->video_path) : null;
        return $msg;
    });

    return response()->json($messages);
}

// ✅ Modifier un message
public function update(Request $request, $id)
{
    $message = Message::findOrFail($id);

    // Vérifier que l'utilisateur connecté est bien l'auteur
    if ($message->sender_id !== auth()->id()) {
        return response()->json(['error' => 'Non autorisé'], 403);
    }

    $request->validate([
        'contenu' => 'nullable|string',
        'image' => 'nullable|file|image|mimes:jpg,jpeg,png,gif|max:2048',
        'video' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime|max:10240',
    ]);

    // ✅ Si un nouveau texte est fourni
    if ($request->has('contenu')) {
        $message->contenu = $request->contenu;
    }

    // ✅ Si une nouvelle image est envoyée
    if ($request->hasFile('image')) {
        if ($message->image_path && Storage::disk('public')->exists($message->image_path)) {
            Storage::disk('public')->delete($message->image_path);
        }
        $message->image_path = $request->file('image')->store('messages/images', 'public');
    }

    // ✅ Si une nouvelle vidéo est envoyée
    if ($request->hasFile('video')) {
        if ($message->video_path && Storage::disk('public')->exists($message->video_path)) {
            Storage::disk('public')->delete($message->video_path);
        }
        $message->video_path = $request->file('video')->store('messages/videos', 'public');
    }

    $message->save();

    return response()->json([
        'success' => true,
        'message' => $message
    ]);
}

// ✅ Supprimer un message
public function destroy($id)
{
    $message = Message::findOrFail($id);

    // Vérifier que c’est bien l’auteur du message
    if ($message->sender_id !== auth()->id()) {
        return response()->json(['error' => 'Non autorisé'], 403);
    }

    // Supprimer fichiers liés
    if ($message->image_path && Storage::disk('public')->exists($message->image_path)) {
        Storage::disk('public')->delete($message->image_path);
    }
    if ($message->video_path && Storage::disk('public')->exists($message->video_path)) {
        Storage::disk('public')->delete($message->video_path);
    }

    $message->delete();

    return response()->json(['success' => true, 'message' => 'Message supprimé']);
}


}
