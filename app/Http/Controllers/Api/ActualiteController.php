<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Actualite;
use App\Models\Commentaire;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Notification;


class ActualiteController extends Controller
{
    // Liste des actualités
   /* public function index()
    {
        $actualites = Actualite::with(['user', 'commentaires.user', 'likes'])->latest()->get();
        return response()->json($actualites);
    }*/

        public function index()
{
    $actualites = Actualite::with(['user', 'commentaires.user', 'likes'])
        ->whereHas('user', function($query) {
            $query->where('is_blocked', false);  // ✅ Exclure les utilisateurs bloqués
        })
        ->latest()
        ->get();
    
    return response()->json($actualites);
}

    // Détail d'une actualité
    public function show($id)
    {
        $actualite = Actualite::with(['user', 'commentaires.user', 'likes'])->findOrFail($id);
        return response()->json($actualite);
    }

    
    // Créer une actualité (admin)
    public function store(Request $request)
{
    $request->validate([
        'titre' => 'required|string|max:255',
        'contenu' => 'required|string',
        'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'video' => 'nullable|file|mimes:mp4,mov,avi,webm|max:10240', // 10 Mo
    ]);
  
       // ✅ AJOUTEZ CES 5 LIGNES ICI
    if (auth()->user()->is_blocked) {
        return response()->json([
            'message' => 'Votre compte est bloqué. Vous ne pouvez pas publier.'
        ], 403);
    }

   $data = $request->only(['titre', 'contenu']);
$data['user_id'] = auth()->id();

if ($request->hasFile('image')) {
    $data['image'] = $request->file('image')->store('actualites/images', 'public');
}

if ($request->hasFile('video')) {
    $data['video'] = $request->file('video')->store('actualites/videos', 'public');
}

$actualite = Actualite::create($data);

// Notifier tous les utilisateurs sauf celui qui publie
$users = \App\Models\User::where('id', '!=', Auth::id())->get();
Notification::send($users, new GenericNotification([
    'message' => "Nouvelle actualité publiée : {$actualite->titre}",
    'type' => 'actualite',
     'target_id' => $actualite->id
]));


    return response()->json([
        'message' => 'Actualité publiée avec succès',
        'data' => $actualite
    ]);
}


 
// ✅ Met à jour une actualité
public function update(Request $request, $id)
{
    $actualite = Actualite::findOrFail($id);

    if ($actualite->user_id !== Auth::id()) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    // Validation
    $validated = $request->validate([
        'titre' => 'required|string|max:255',
        'contenu' => 'required|string',
        'image' => 'nullable|file|image|mimes:jpg,jpeg,png,gif|max:2048',
        'video' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mpeg|max:10240'
    ]);

// Texte
$actualite->titre = $request->input('titre', '');
$actualite->contenu = $request->input('contenu', '');


// IMAGE
if ($request->hasFile('image')) {
    if ($actualite->image) Storage::delete('public/' . $actualite->image);
    $actualite->image = $request->file('image')->store('actualites/images', 'public');
}


// VIDEO
if ($request->hasFile('video')) {
    if ($actualite->video) Storage::delete('public/' . $actualite->video);
    $actualite->video = $request->file('video')->store('actualites/videos', 'public');
}


// Même logique pour vidéo
$actualite->save();


 Log::info('Request update:', $request->all());
    return response()->json([
        'message' => 'Actualité mise à jour avec succès',
        'actualite' => $actualite
    ]);

   
}



    // Supprimer une actualité
public function destroy($id)
{
    $actualite = Actualite::findOrFail($id);

    if ($actualite->user_id !== Auth::id()) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $actualite->delete();
    return response()->json(['message' => 'Actualité supprimée']);
}


    // Ajouter un commentaire
    public function commenter(Request $request, $id)
    {
        $request->validate(['contenu' => 'required|string']);
        $commentaire = Commentaire::create([
            'actualite_id' => $id,
            'user_id' => Auth::id(),
            'contenu' => $request->contenu
        ]);

        
$actualite = Actualite::find($id);
if($actualite->user_id !== Auth::id()){
    $actualite->user->notify(new GenericNotification([
        'message' => Auth::user()->name . " a commenté votre actualité : {$actualite->titre}",
        'type' => 'commentaire',
        'target_id' => $actualite->id  
    ]));
}

        return response()->json($commentaire, 201);
    }

    // Liker / Unliker
    public function toggleLike($id)
    {
        $like = Like::where('actualite_id', $id)->where('user_id', Auth::id())->first();

        if ($like) {
            $like->delete();
            return response()->json(['liked' => false]);
        } else {
            Like::create(['actualite_id' => $id, 'user_id' => Auth::id()]);
            $actualite = Actualite::find($id);
if($actualite->user_id !== Auth::id()){
    $actualite->user->notify(new GenericNotification([
        'message' => Auth::user()->name . " a aimé votre actualité : {$actualite->titre}",
        'type' => 'like',
        'target_id' => $actualite->id  
    ]));
}

            return response()->json(['liked' => true]);
        }
    }

    public function getMesActualites()
{
    $userId = Auth::id();
    $actualites = Actualite::where('user_id', $userId)->get();

    return response()->json($actualites);
}


public function getByUser($id)
{
    $publications = \App\Models\Actualite::with(['user', 'commentaires', 'likes'])
        ->where('user_id', $id)
        ->latest()
        ->get();

    return response()->json($publications, 200);
}


public function search(Request $request)
{
    $query = $request->input('query', '');

    if (trim($query) === '') {
        return response()->json([]);
    }

    try {
        $actualites = Actualite::with('user')
            ->where(function ($q) use ($query) {
                $q->where('titre', 'like', "%{$query}%")
                  ->orWhere('contenu', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($actualites);
    } catch (\Exception $e) {
        \Log::error('Erreur recherche actualités: ' . $e->getMessage());
        return response()->json(['message' => 'Erreur serveur'], 500);
    }
}





}
