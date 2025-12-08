<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Notification;


class FollowController extends Controller
{
    // 🔹 Suivre un utilisateur
   /* public function follow($id)
    {
        $user = Auth::user();

        if ($user->id == $id) {
            return response()->json(['message' => "Vous ne pouvez pas vous suivre vous-même"], 400);
        }

        $toFollow = User::findOrFail($id);

        // Vérifie si déjà suivi
        if ($user->following()->where('follow_id', $id)->exists()) {
            return response()->json(['message' => "Vous suivez déjà cet utilisateur"], 400);
        }

        $user->following()->attach($id);
        $toFollow->notify(new GenericNotification([
    'message' => "{$user->name} a commencé à vous suivre",
    'type' => 'follow',
     'target_id' => $authUser->id // le profil de celui qui suit
]));


        return response()->json(['message' => "Vous suivez maintenant {$toFollow->name}"]);
    }

    // 🔹 Ne plus suivre un utilisateur
    public function unfollow($id)
    {
        $user = Auth::user();
        $toUnfollow = User::findOrFail($id);

        if (!$user->following()->where('follow_id', $id)->exists()) {
            return response()->json(['message' => "Vous ne suivez pas cet utilisateur"], 400);
        }

        $user->following()->detach($id);
        $userToFollow->notify(new GenericNotification([
    'message' => "{$authUser->name} a commencé à vous suivre",
    'type' => 'follow',
     'target_id' => $authUser->id // le profil de celui qui suit
]));


        return response()->json(['message' => "Vous ne suivez plus {$toUnfollow->name}"]);
    }*/

        // 🔹 Suivre un utilisateur
public function follow($id)
{
    $user = Auth::user();
    if ($user->id == $id) {
        return response()->json(['message' => "Vous ne pouvez pas vous suivre vous-même"], 400);
    }
    $toFollow = User::findOrFail($id);
    // Vérifie si déjà suivi
    if ($user->following()->where('follow_id', $id)->exists()) {
        return response()->json(['message' => "Vous suivez déjà cet utilisateur"], 400);
    }
    $user->following()->attach($id);
    $toFollow->notify(new GenericNotification([
        'message' => "{$user->name} a commencé à vous suivre",
        'type' => 'follow',
        'target_id' => $user->id // ✅ CORRIGÉ : $authUser → $user
    ]));
    return response()->json(['message' => "Vous suivez maintenant {$toFollow->name}"]);
}

// 🔹 Ne plus suivre un utilisateur
public function unfollow($id)
{
    $user = Auth::user();
    $toUnfollow = User::findOrFail($id);
    if (!$user->following()->where('follow_id', $id)->exists()) {
        return response()->json(['message' => "Vous ne suivez pas cet utilisateur"], 400);
    }
    $user->following()->detach($id);
    $toUnfollow->notify(new GenericNotification([ // ✅ CORRIGÉ : $userToFollow → $toUnfollow
        'message' => "{$user->name} ne vous suit plus", // ✅ CORRIGÉ : $authUser → $user
        'type' => 'unfollow', // ✅ BONUS : Type corrigé
        'target_id' => $user->id // ✅ CORRIGÉ : $authUser → $user
    ]));
    return response()->json(['message' => "Vous ne suivez plus {$toUnfollow->name}"]);
}

    // 🔹 Liste des utilisateurs que l'utilisateur connecté suit
    public function following()
    {
        $user = Auth::user();
        $following = $user->following()->get();

        return response()->json($following);
    }

    // 🔹 Liste des utilisateurs qui suivent l'utilisateur connecté
    public function followers()
    {
        $user = Auth::user();
        $followers = $user->followers()->get();

        return response()->json($followers);
    }

    public function toggleFollow($id)
{
    $userToFollow = User::findOrFail($id);
    $authUser = Auth::user();

    if ($authUser->id === $userToFollow->id) {
        return response()->json(['message' => 'Vous ne pouvez pas vous suivre vous-même'], 400);
    }

   if ($authUser->following()->where('follow_id', $id)->exists()) {
    // Déjà suivi → on désabonne
    $authUser->following()->detach($id);
    // Notification de désabonnement
            $userToFollow->notify(new GenericNotification([
                'message' => "{$authUser->name} a arrêté de vous suivre",
                'type' => 'unfollow',
                'target_id' => $authUser->id
            ]));
    return response()->json(['message' => 'Désabonné avec succès']);
} else {
    // On suit
    $authUser->following()->attach($id);
         // Notification de suivi
            $userToFollow->notify(new GenericNotification([
                'message' => "{$authUser->name} a commencé à vous suivre",
                'type' => 'follow',
                'target_id' => $authUser->id
            ]));
    return response()->json(['message' => 'Abonné avec succès']);
}

}

}
