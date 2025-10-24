<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // 🔹 Récupérer tous les messages reçus
    public function index() {
        return Message::where('receiver_id', Auth::id())
                      ->with('sender:id,name')
                      ->orderBy('created_at', 'desc')
                      ->get();
    }

    // 🔹 Envoyer un message
    public function store(Request $request) {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'contenu' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'contenu' => $request->contenu
        ]);

        // Créer une notification pour le destinataire
        Notification::create([
            'user_id' => $request->receiver_id,
            'type' => 'message',
            'contenu' => 'Vous avez reçu un nouveau message de ' . Auth::user()->name
        ]);

        return response()->json($message, 201);
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
            // Identifier l’autre utilisateur dans la conversation
            $otherUser = $msg->sender_id === $userId ? $msg->receiver : $msg->sender;

            if (!isset($conversations[$otherUser->id])) {
                $conversations[$otherUser->id] = [
                    'user_id' => $otherUser->id,
                    'user_name' => $otherUser->name, // ✅ Nom de la personne
                    'last_message' => $msg->contenu,
                    'updated_at' => $msg->created_at->diffForHumans(),
                ];
            }
        }

        // Retourner la liste des conversations
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

    // 🔹 Récupérer la conversation complète entre deux utilisateurs
    public function getMessagesWithUser($userId)
    {
        $currentUserId = auth()->id();

        $messages = Message::where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)
                  ->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)
                  ->where('receiver_id', $currentUserId);
            })
            ->with('sender:id,name') // ✅ Charger le nom de l’expéditeur
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }
}
