<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;

class SuggestionController extends Controller
{
public function index()
{
    $currentUserId = Auth::id();

    $users = User::with('profil') // 🔹 ajoute la relation profil
        ->where('id', '!=', $currentUserId)
        ->where('role', '!=', 'admin')
        ->inRandomOrder()
        ->limit(3)
        ->get()
        ->map(function ($u) use ($currentUserId) {
            $u->isFollowed = \App\Models\User::find($currentUserId)
                ->following()
                ->where('follow_id', $u->id)
                ->exists();

            // 🔹 Ajouter la photo du profil
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'photo' => $u->profil ? $u->profil->photo : null,
                'role' => $u->role, // 🔹 ajouter le rôle ici
                'isFollowed' => $u->isFollowed,
            ];
        });

    return response()->json($users, 200);
}


public function allUsers()
{
    $currentUserId = Auth::id();

    $users = User::with('profil') // 🔹 ajoute la relation profil
        ->where('id', '!=', $currentUserId)
        ->where('role', '!=', 'admin')
        ->get()
        ->map(function ($u) use ($currentUserId) {
            $u->isFollowed = \App\Models\User::find($currentUserId)
                ->following()
                ->where('follow_id', $u->id)
                ->exists();

            // 🔹 Ajouter la photo du profil
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'photo' => $u->profil ? $u->profil->photo : null,
                'role' => $u->role, // 🔹 ajouter le rôle ici
                'isFollowed' => $u->isFollowed,
            ];
        });

    return response()->json($users, 200);
}



}
