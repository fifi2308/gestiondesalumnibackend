<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Groupe;
use App\Models\Discussion;
use Illuminate\Support\Facades\Auth;
use App\Notifications\GenericNotification; // Pour créer toutes les notifications génériques
use Illuminate\Support\Facades\Notification;
use App\Models\User;



class GroupeController extends Controller
{
    // ✅ Liste des groupes avec nombre de membres
    public function index()
    {
        $user = auth()->user();

        $groupes = Groupe::withCount('membres')->get();

        $groupes->transform(function($groupe) use ($user) {
            $groupe->isMember = $user ? $groupe->membres()->where('user_id', $user->id)->exists() : false;
            return $groupe;
        });

        return response()->json($groupes);
    }

    // ✅ Détails d’un groupe
    public function show($id)
    {
        $groupe = Groupe::withCount('membres')->with('membres')->findOrFail($id);
        $user = Auth::user();

        $isMember = $user ? $groupe->membres->contains($user->id) : false;

        return response()->json([
            'groupe' => $groupe,
            'isMember' => $isMember,
        ]);
    }

    // ✅ Intégrer un groupe
    public function join($id)
    {
        $user = Auth::user();
        $groupe = Groupe::findOrFail($id);

                // ❌ Empêcher un utilisateur banni de réintégrer le groupe
if ($groupe->bannis()->where('user_id', $user->id)->exists()) {
    return response()->json(['message' => 'Vous avez été retiré de ce groupe, vous ne pouvez plus le rejoindre.'], 403);
}

        if ($groupe->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous êtes déjà membre de ce groupe.'], 200);
        }

        $groupe->membres()->attach($user->id);


        // Notification pour l'utilisateur qui a rejoint
$user->notify(new GenericNotification([
    'message' => "Vous avez rejoint le groupe : {$groupe->nom}",
    'type' => 'groupe',
     'target_id' => $groupe->id // redirection vers le groupe
]));

// Optionnel : notification pour l’admin ou le créateur du groupe
$groupeOwner = $groupe->user; // si le modèle Groupe a une relation user()
if($groupeOwner && $groupeOwner->id !== $user->id){
    $groupeOwner->notify(new GenericNotification([
        'message' => "{$user->name} a rejoint votre groupe : {$groupe->nom}",
        'type' => 'groupe',
         'target_id' => $groupe->id // redirection vers le groupe
    ]));
}


        return response()->json([
            'message' => 'Vous avez rejoint le groupe avec succès.',
            'membres_count' => $groupe->membres()->count()
        ]);
    }

    // ✅ Créer un groupe (admin uniquement)
    /*public function store(Request $request)
    {
        $user = auth()->user();

        // ✅ Vérifie si admin
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé — seuls les administrateurs peuvent créer un groupe.'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $validated['user_id'] = $user->id;

        $groupe = Groupe::create($validated);
        return response()->json($groupe);
    }*/

        public function store(Request $request)
{
    $user = auth()->user();

    // 🔥 Plus de restriction admin. Tout utilisateur connecté peut créer un groupe.
    $validated = $request->validate([
        'nom' => 'required|string',
        'description' => 'nullable|string'
    ]);

    // 🔥 On enregistre automatiquement le créateur
    $validated['user_id'] = $user->id;

    $groupe = Groupe::create($validated);

    // 🔥 Ajouter automatiquement le créateur comme membre
    $groupe->membres()->attach($user->id);

    return response()->json($groupe);
}


 

        public function update(Request $request, $id)
{
    $user = auth()->user();
    $groupe = Groupe::findOrFail($id);

    // 🔥 Seulement admin OU créateur
    if ($user->role !== 'admin' && $groupe->user_id !== $user->id) {
        return response()->json(['message' => 'Vous n\'avez pas la permission de modifier ce groupe'], 403);
    }

    $groupe->update($request->all());
    return response()->json($groupe);
}


    // ✅ Supprimer un groupe (admin uniquement)
   /* public function destroy($id)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé — seuls les administrateurs peuvent supprimer un groupe.'], 403);
        }

        $groupe = Groupe::findOrFail($id);
        $groupe->delete();
        return response()->json(['message' => 'Groupe supprimé']);
    }*/

        public function destroy($id)
{
    $user = auth()->user();
    $groupe = Groupe::findOrFail($id);

    // 🔥 Seulement admin OU créateur
    if ($user->role !== 'admin' && $groupe->user_id !== $user->id) {
        return response()->json(['message' => 'Vous n\'avez pas la permission de supprimer ce groupe'], 403);
    }

    $groupe->delete();
    return response()->json(['message' => 'Groupe supprimé']);
}


    // ✅ Discussions du groupe
    public function discussions($id)
    {
        $discussions = Discussion::with('user')
            ->where('group_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($discussions);
    }

    // ✅ Poster une discussion
    public function postDiscussion(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'file' => 'nullable|file|max:10240'
        ]);

        $data = [
            'group_id' => $id,
            'user_id' => auth()->id(),
            'message' => $request->message
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            $data['file_path'] = asset('storage/' . $path);
        }

        $discussion = Discussion::create($data);
        // Notifier tous les membres du groupe sauf celui qui poste
$groupe = Groupe::find($id);
$user = Auth::user();

$members = $groupe->membres()->where('user_id', '!=', $user->id)->get();

Notification::send($members, new GenericNotification([
    'message' => "{$user->name} a posté un nouveau message dans le groupe : {$groupe->nom}",
    'type' => 'groupe',
     'target_id' => $groupe->id ,
     'data' => [
        'group_name' => $groupe->nom
    ]
]));

        return response()->json($discussion->load('user'));
    }

    // ✅ Modifier une discussion
    public function updateDiscussion(Request $request, $id)
    {
        $discussion = Discussion::findOrFail($id);

        if ($discussion->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate(['message' => 'required|string']);
        $discussion->update(['message' => $request->message]);

        return response()->json($discussion);
    }

    // ✅ Supprimer une discussion
    public function deleteDiscussion($id)
    {
        $discussion = Discussion::findOrFail($id);

        if ($discussion->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $discussion->delete();
        return response()->json(['message' => 'Discussion supprimée']);
    }

    // ✅ Quitter un groupe
    public function quit($id)
    {
        $user = Auth::user();
        $groupe = Groupe::findOrFail($id);

        if (!$groupe->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous ne faites pas partie de ce groupe.'], 400);
        }

        $groupe->membres()->detach($user->id);

        // 🔹 Notification pour informer éventuellement le propriétaire
        $groupeOwner = $groupe->user;
        if($groupeOwner && $groupeOwner->id !== $user->id){
            $groupeOwner->notify(new GenericNotification([
                'message' => "{$user->name} a quitté le groupe : {$groupe->nom}",
                'type' => 'groupe',
                'target_id' => $groupe->id
            ]));
        }

        return response()->json([
            'message' => 'Vous avez quitté le groupe avec succès.',
            'membres_count' => $groupe->membres()->count()
        ]);
    }

    public function populaires()
    {
        $user = auth()->user();
        $groupes = Groupe::withCount('membres')
            ->orderBy('membres_count', 'desc')
            ->take(5)
            ->get();

        $groupes->transform(function ($groupe) use ($user) {
            $groupe->isMember = $user ? $groupe->membres()->where('user_id', $user->id)->exists() : false;
            return $groupe;
        });

        return $groupes;
    }



  /*  public function removeUser($groupe_id, $user_id)
{
    $user = Auth::user();
    $groupe = Groupe::findOrFail($groupe_id);

    // Vérifie que seul le créateur du groupe peut retirer des gens
    if ($groupe->user_id !== $user->id) {
        return response()->json(['message' => 'Vous n\'avez pas la permission de retirer un membre'], 403);
    }

    // Vérifie que l’utilisateur est membre
    if (!$groupe->membres()->where('user_id', $user_id)->exists()) {
        return response()->json(['message' => 'Cet utilisateur ne fait pas partie du groupe'], 400);
    }

    // Retirer de la table groupe_user
    $groupe->membres()->detach($user_id);

    // L’ajouter dans la liste des bannis
    $groupe->bannis()->attach($user_id);

    return response()->json(['message' => 'Utilisateur retiré et banni du groupe']);
}*/

public function removeUser($groupe_id, $user_id)
{
    $user = Auth::user();
    $groupe = Groupe::findOrFail($groupe_id);

    // Vérifie que seul le créateur du groupe peut retirer des gens
    if ($groupe->user_id !== $user->id) {
        return response()->json(['message' => 'Vous n\'avez pas la permission de retirer un membre'], 403);
    }

    // Vérifie que l’utilisateur est membre
    if (!$groupe->membres()->where('user_id', $user_id)->exists()) {
        return response()->json(['message' => 'Cet utilisateur ne fait pas partie du groupe'], 400);
    }

    // Retirer de la table groupe_user
    $groupe->membres()->detach($user_id);

    // Supprimer tous ses messages dans le groupe
    Discussion::where('group_id', $groupe_id)
              ->where('user_id', $user_id)
              ->delete();

    // L’ajouter dans la liste des bannis pour qu’il ne puisse plus revenir
    $groupe->bannis()->attach($user_id);

    return response()->json(['message' => 'Utilisateur retiré, banni et messages supprimés du groupe']);
}


public function members($id)
{
    $groupe = Groupe::with('membres')->find($id); // corrige le nom du modèle

    if (!$groupe) {
        return response()->json(['message' => 'Groupe non trouvé'], 404);
    }

    // renvoyer les membres sauf le créateur si tu veux
    $members = $groupe->membres->filter(function($m) use ($groupe) {
        return $m->id !== $groupe->user_id;
    });

    return response()->json($members);
}

public function addUser(Request $request, $groupId)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $group = Groupe::findOrFail($groupId);

    // Vérifie si l'utilisateur n'est pas déjà membre
    if ($group->membres()->where('user_id', $request->user_id)->exists()) {
        return response()->json(['message' => 'Utilisateur déjà membre'], 400);
    }

    $group->membres()->attach($request->user_id);

    $user = User::find($request->user_id);

    return response()->json($user, 201); // Renvoie les infos du membre ajouté
}

public function getUserGroups(Request $request)
{
    $userId = $request->user()->id;

    // Groupes où l'utilisateur est membre ou créateur
    $mesGroupes = Groupe::where('user_id', $userId)
        ->orWhereHas('membres', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

    // Groupes disponibles (pas encore intégré)
    $groupesDisponibles = Groupe::whereDoesntHave('membres', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('user_id', '!=', $userId)->get();

    return response()->json([
        'mes_groupes' => $mesGroupes,
        'groupes_disponibles' => $groupesDisponibles
    ]);
}









}
