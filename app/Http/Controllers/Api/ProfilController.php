<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;


class ProfilController extends Controller
{
    // ✅ Récupérer le profil de l'utilisateur connecté
    public function index()
    {
        $profil = Profil::where('user_id', Auth::id())->with('user')->first();

        if (!$profil) {
            return response()->json([
                'message' => 'Aucun profil trouvé'
            ], 404);
        }

        return response()->json([
            'profil' => $profil,
            'user' => [
                'name' => $profil->user->name,
                'email' => $profil->user->email,
                'role' => $profil->user->role,
            ]
        ]);
    }

    // ✅ Créer un profil pour l'utilisateur connecté
    public function store(Request $request)
    {
        if (Profil::where('user_id', Auth::id())->exists()) {
            return response()->json([
                'message' => 'Vous avez déjà un profil'
            ], 400);
        }

        $request->validate([
            'parcours_academique' => 'nullable|string',
            'experiences_professionnelles' => 'nullable|string',
            'competences' => 'nullable|string',
            'realisations' => 'nullable|string',
            'bio' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255', // ✅ ajout adresse
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only([
            'parcours_academique',
            'experiences_professionnelles',
            'competences',
            'realisations',
            'bio',
            'adresse', // ✅ ajout adresse
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $data['photo'] = url('storage/' . $path); // URL complète
        }

        $data['user_id'] = Auth::id();

        $profil = Profil::create($data);

        return response()->json([
            'message' => 'Profil créé avec succès 🎉',
            'profil' => $profil
        ], 201);
    }

    // ✅ Mettre à jour le profil de l'utilisateur connecté
    public function update(Request $request)
    {
        $profil = Profil::where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'parcours_academique' => 'nullable|string',
            'experiences_professionnelles' => 'nullable|string',
            'competences' => 'nullable|string',
            'realisations' => 'nullable|string',
            'bio' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255', // ✅ ajout adresse
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only([
            'parcours_academique',
            'experiences_professionnelles',
            'competences',
            'realisations',
            'bio',
            'adresse', // ✅ ajout adresse
        ]);

        if ($request->hasFile('photo')) {
            if ($profil->photo && Storage::disk('public')->exists(str_replace(url('storage/'), '', $profil->photo))) {
                Storage::disk('public')->delete(str_replace(url('storage/'), '', $profil->photo));
            }
            $path = $request->file('photo')->store('photos', 'public');
            $data['photo'] = url('storage/' . $path);
        }

        $profil->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès ✅',
            'profil' => $profil
        ]);
    }

    // ✅ Supprimer le profil de l'utilisateur connecté
    public function destroy()
    {
        $profil = Profil::where('user_id', Auth::id())->firstOrFail();

        if ($profil->photo && Storage::disk('public')->exists(str_replace('/storage/', '', $profil->photo))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $profil->photo));
        }

        $profil->delete();

        return response()->json(['message' => 'Profil supprimé avec succès 🗑️']);
    }



public function getUserProfil()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non connecté'], 401);
    }

    $profil = Profil::where('user_id', $user->id)->first();

    // 🔹 Si le profil n'existe pas encore, on renvoie une structure vide plutôt qu'une erreur 404
    if (!$profil) {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'profil' => null,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'nombre_actualites' => $user->actualites()->count(),
        ]);
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ],
        'profil' => $profil,
        'followers_count' => $user->followers()->count(),
        'following_count' => $user->following()->count(),
        'nombre_actualites' => $user->actualites()->count(),
    ]);
}

public function getFollowers($id)
{
    $user = \App\Models\User::find($id);

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé'], 404);
    }

    $followers = $user->followers()->get(['users.id', 'users.name', 'users.email']);

    return response()->json($followers);
}

public function getFollowing($id)
{
    $user = \App\Models\User::find($id);

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé'], 404);
    }

    $following = $user->following()->get(['users.id', 'users.name', 'users.email']);

    return response()->json($following);
}

public function showPublic($id)
{
    $currentUserId = auth()->id(); // 🔹 définir d’abord

    // Charger l'utilisateur avec son profil
    $user = User::with('profil')->find($id);

    if (!$user) {
        return response()->json(['message' => 'Utilisateur non trouvé'], 404);
    }

    // ❌ Bloquer si l’utilisateur est lui-même ou admin
    if ($user->id === $currentUserId || $user->role === 'admin') {
        return response()->json([
            'message' => 'Accès refusé.'
        ], 403);
    }

    // Vérifier si l'utilisateur connecté suit ce profil
    $isFollowing = $currentUserId 
        ? auth()->user()->following()->where('follow_id', $user->id)->exists() 
        : false;

    // Préparer la photo
    $photo = null;
    if ($user->profil && $user->profil->photo) {
        $photo = $user->profil->photo;
        if (!str_starts_with($photo, 'http')) {
            $photo = url('storage/' . $photo);
        }
    }

    // Retourner les données pour le frontend
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'photo' => $photo,
        ],
        'profil' => $user->profil ?: null,
        'followers_count' => $user->followers()->count(),
        'following_count' => $user->following()->count(),
        'is_following' => $isFollowing,
    ]);
}





}
