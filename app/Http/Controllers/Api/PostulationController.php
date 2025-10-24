<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Postulation;
use App\Models\Offre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostulationController extends Controller
{
    // 🔹 Lister toutes les postulations
    public function index()
    {
        return response()->json(Postulation::with(['offre', 'user'])->get());
    }

    // 🔹 Enregistrer une nouvelle postulation avec formulaire complet
    public function store(Request $request)
    {
        $user = Auth::user();

        // 🔹 Validation
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:50',
            'cv' => 'required|file|mimes:pdf|max:2048',
            'message' => 'nullable|string',
        ]);

        // 🔹 Vérifier si l'utilisateur a déjà postulé à cette offre
        $exists = Postulation::where('offre_id', $request->offre_id)
                              ->where('user_id', $user->id)
                              ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre.'], 400);
        }

        // 🔹 Stocker le CV
        $cvPath = $request->file('cv')->store('cvs', 'public');

        // 🔹 Créer la postulation
        $postulation = Postulation::create([
            'offre_id' => $request->offre_id,
            'user_id' => $user->id,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'cv' => $cvPath,
            'message' => $request->message ?? ''
        ]);

        return response()->json([
            'message' => 'Candidature envoyée avec succès.',
            'postulation' => $postulation
        ], 201);
    }

    // 🔹 Afficher une postulation spécifique
    public function show($id)
    {
        $postulation = Postulation::with(['offre', 'user'])->find($id);

        if (!$postulation) {
            return response()->json(['message' => 'Postulation non trouvée'], 404);
        }

        return response()->json($postulation);
    }

    // 🔹 Supprimer une postulation
    public function destroy($id)
    {
        $postulation = Postulation::find($id);

        if (!$postulation) {
            return response()->json(['message' => 'Postulation non trouvée'], 404);
        }

        // 🔹 Supprimer le CV du storage
        if ($postulation->cv && Storage::disk('public')->exists($postulation->cv)) {
            Storage::disk('public')->delete($postulation->cv);
        }

        $postulation->delete();

        return response()->json(['message' => 'Postulation supprimée avec succès']);
    }
}
