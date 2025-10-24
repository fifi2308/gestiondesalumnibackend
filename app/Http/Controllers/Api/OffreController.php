<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offre;
use Illuminate\Support\Facades\Auth;

class OffreController extends Controller
{
    // Liste toutes les offres
    public function index() {
        return Offre::with('user')->get();
    }

    // Voir une offre
    public function show($id) {
        return Offre::with('user','postulants')->findOrFail($id);
    }

    // Publier une offre (admin/entreprise/alumni)
    public function store(Request $request) {
        $user = Auth::user();
        if (!in_array($user->role, ['admin','entreprise','alumni'])) {
            return response()->json(['message'=>'Non autorisé'], 403);
        }

        $request->validate([
            'titre'=>'required|string',
            'description'=>'required|string',
            'type'=>'required|in:emploi,stage'
        ]);

        $offre = Offre::create([
            'user_id' => $user->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type
        ]);

        return response()->json($offre, 201);
    }

    // Postuler à une offre (etudiant/alumni)
    public function postuler(Request $request, $id) {
        $user = Auth::user();
        if (!in_array($user->role, ['etudiant','alumni'])) {
            return response()->json(['message'=>'Seuls les étudiants et alumni peuvent postuler'], 403);
        }

        $offre = Offre::findOrFail($id);
        $offre->postulants()->attach($user->id, ['message' => $request->message]);
        return response()->json(['message'=>'Postulation réussie']);
    }

    // Supprimer une offre
    public function destroy($id) {
        $user = Auth::user();
        $offre = Offre::findOrFail($id);
        if ($offre->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message'=>'Non autorisé'], 403);
        }
        $offre->delete();
        return response()->json(['message'=>'Offre supprimée']);
    }
}
