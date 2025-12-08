<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offre;
use Illuminate\Support\Facades\Auth;
use App\Notifications\GenericNotification; // ✅ Import correct
use App\Models\User;

class OffreController extends Controller
{
    // Liste toutes les offres
    public function index()
    {
        return Offre::with('user')
            ->whereHas('user', function ($q) {
                $q->where('is_blocked', false);
            })
            ->get();
    }

    public function show($id)
    {
        // Si l'utilisateur demande la dernière offre
        if ($id === 'recent') {
            return Offre::with('user', 'postulants')
                        ->orderBy('created_at', 'desc')
                        ->firstOrFail();
        }

        // Vérifie si l'ID est bien un entier
        if (!ctype_digit($id)) {
            abort(400, 'ID invalide.');
        }

        // Sinon, on cherche par ID numérique
        return Offre::with('user', 'postulants')->findOrFail($id);
    }

    // Publier une offre (admin/entreprise/alumni)
    public function store(Request $request) 
    {
        $user = Auth::user();
        
        // 🔒 Empêcher un utilisateur bloqué de publier une offre
        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Vous êtes bloqué et ne pouvez pas publier une offre.'
            ], 403);
        }

        if (!in_array($user->role, ['admin','entreprise','alumni'])) {
            return response()->json(['message'=>'Non autorisé'], 403);
        }

        // ✅ Validation
        $request->validate([
            'titre' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|in:emploi,stage',
            'date_expiration' => 'required|date'
        ]);

        // ✅ Vérification manuelle de la date
        if ($request->date_expiration < now()->toDateString()) {
            return response()->json([
                'message' => 'La date d\'expiration doit être aujourd\'hui ou dans le futur.'
            ], 422);
        }

        // ✅ Création de l'offre
        $offre = Offre::create([
            'user_id' => $user->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'date_expiration' => $request->date_expiration
        ]);

        // Charger la relation user
        $offre->load('user');

        // 🔹 Notifications pour tous les autres utilisateurs
        $otherUsers = User::where('id', '<>', $user->id)->get();
        foreach ($otherUsers as $u) {
            $u->notify(new GenericNotification([
                'message' => "Nouvelle offre publiée : {$offre->titre}",
                'type' => 'offre',
                'target_id' => $offre->id
            ]));
        }

        return response()->json($offre, 201);
    }

    // Postuler à une offre (etudiant/alumni)
    public function postuler(Request $request, $id) 
    {
        $user = Auth::user();
        
        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Vous êtes bloqué et ne pouvez pas postuler.'
            ], 403);
        }

        if (!in_array($user->role, ['etudiant','alumni'])) {
            return response()->json(['message'=>'Seuls les étudiants et alumni peuvent postuler'], 403);
        }

        $offre = Offre::findOrFail($id);
        
        // ✅ Vérifier si l'offre est expirée
        if ($offre->date_expiration && $offre->date_expiration < now()->toDateString()) {
            return response()->json([
                'message' => 'Cette offre a expiré, vous ne pouvez plus postuler.'
            ], 403);
        }
        
        $offre->postulants()->attach($user->id, ['message' => $request->message]);
        
        // 🔹 Notification pour le propriétaire de l'offre
        $offreOwner = $offre->user;
        if ($offreOwner && $offreOwner->id !== $user->id) {
            $offreOwner->notify(new GenericNotification([
                'message' => "{$user->name} a postulé à votre offre : {$offre->titre}",
                'type' => 'postulation',
                'target_id' => $offre->id
            ]));
        }
        
        return response()->json(['message'=>'Postulation réussie']);
    }

    public function recent()
    {
        $offres = Offre::with('user')
            ->whereHas('user', function($q) {
                $q->where('is_blocked', false);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($offres);
    }

    // Supprimer une offre
    public function destroy($id) 
    {
        $user = Auth::user();
        $offre = Offre::findOrFail($id);
        
        if ($offre->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message'=>'Non autorisé'], 403);
        }
        
        $offre->delete();
        return response()->json(['message'=>'Offre supprimée']);
    }

    // Modifier une offre
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $offre = Offre::findOrFail($id);

        // Seul le propriétaire ou un admin peut modifier
        if ($offre->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // ✅ Validation
        $request->validate([
            'titre' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|in:emploi,stage',
            'date_expiration' => 'required|date'
        ]);

        // ✅ Vérification manuelle de la date
        if ($request->date_expiration < now()->toDateString()) {
            return response()->json([
                'message' => 'La date d\'expiration doit être aujourd\'hui ou dans le futur.'
            ], 422);
        }

        // ✅ Mise à jour
        $offre->update([
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'date_expiration' => $request->date_expiration
        ]);

        // Charger la relation user
        $offre->load('user');

        return response()->json([
            'message' => 'Offre mise à jour avec succès', 
            'offre' => $offre
        ]);
    }

    public function getPostulations($offre_id)
    {
        $user = Auth::user();

        // Trouver l'offre et vérifier que l'utilisateur est bien son créateur
        $offre = Offre::with('postulants')->findOrFail($offre_id);

        if ($offre->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Charger les postulants avec leurs infos utilisateur
        $postulations = \App\Models\Postulation::with('user')
                        ->where('offre_id', $offre_id)
                        ->get();

        return response()->json($postulations);
    }
}