<?php

/*namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Evenement;
use App\Models\Notification;
use App\Notifications\EvenementNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Models\User;




class EvenementController extends Controller
{
    public function index(){
        return Evenement::with('users')->get();
    }

    public function show($id){
        return Evenement::with('users')->findOrFail($id);
    }

    public function store(Request $request){
    $user = Auth::user();
    if ($user->role !== 'admin') {
        return response()->json(['message'=>'Non autorisé'], 403);
    }

    $request->validate([
        'titre'=>'required|string',
        'description'=>'required|string',
        'date'=>'required|date',
        'lieu'=>'nullable|string'
    ]);

    $evenement = Evenement::create($request->all());

    // 🔹 Notification à tous les utilisateurs (sauf l'admin qui crée)
$otherUsers = User::where('id', '<>', $user->id)->get();
foreach ($otherUsers as $u) {
    $u->notify(new EvenementNotification($evenement));
}
$admins = User::where('role', 'admin')->get();
foreach ($admins as $admin) {
    $admin->notify(new EvenementNotification($evenement, "{$user->name} s'est inscrit à l'événement {$evenement->titre}"));
}

    return response()->json($evenement, 201);
}


    public function update(Request $request, $id){
    $evenement = Evenement::findOrFail($id);
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json(['message'=>'Non autorisé'], 403);
    }

    $request->validate([
        'titre'=>'required|string',
        'description'=>'required|string',
        'date'=>'required|date',
        'lieu'=>'nullable|string'
    ]);

    $evenement->update($request->all());
    return response()->json($evenement);
}


    public function destroy($id){
    $evenement = Evenement::findOrFail($id);
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json(['message'=>'Non autorisé'], 403);
    }

    $evenement->delete();
    return response()->json(['message'=>'Événement supprimé']);
}


public function inscrire($id)
{
    $evenement = Evenement::findOrFail($id);
    $user = Auth::user();

    // Vérifie si l'utilisateur est déjà inscrit
    if ($evenement->users()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Vous êtes déjà inscrit'], 400);
    }

    // Inscription de l'utilisateur à l'événement
    $evenement->users()->attach($user->id);

    // Notification via le système Laravel
    $user->notify(new EvenementNotification($evenement));

    return response()->json(['message' => 'Inscription réussie']);
}

public function participants($id){
    $evenement = Evenement::with('users')->findOrFail($id);
    return response()->json($evenement->users);
}
// Récupérer les 3 prochains événements
public function upcomingLimited() {
    $events = Evenement::orderBy('date', 'asc')
                       ->take(3)
                       ->get();
    return response()->json($events);
}

public function upcoming()
{
    $evenements = Evenement::where('date', '>=', now())
                            ->orderBy('date', 'asc')
                            ->get();
    return response()->json($evenements);
}


}
*/



namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Evenement;
use App\Notifications\EvenementNotification;
use App\Models\User;

class EvenementController extends Controller
{
    public function index(){
        return Evenement::with('users')->get();
    }

    public function show($id){
        return Evenement::with('users')->findOrFail($id);
    }

    public function store(Request $request){
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message'=>'Non autorisé'], 403);
        }

        $request->validate([
            'titre'=>'required|string',
            'description'=>'required|string',
            'date'=>'required|date',
            'lieu'=>'nullable|string'
        ]);

        $evenement = Evenement::create($request->all());

        // 🔹 Notification à tous les utilisateurs (sauf l'admin qui crée)
        $otherUsers = User::where('id', '<>', $user->id)->get();
        foreach ($otherUsers as $u) {
            $u->notify(new EvenementNotification($evenement));
        }

        // 🔹 Notification aux admins sur inscription (avec message personnalisé)
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new EvenementNotification(
                $evenement, 
                "{$user->name} s'est inscrit à l'événement {$evenement->titre}"
            ));
        }

        return response()->json($evenement, 201);
    }

    public function update(Request $request, $id){
        $evenement = Evenement::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message'=>'Non autorisé'], 403);
        }

        $request->validate([
            'titre'=>'required|string',
            'description'=>'required|string',
            'date'=>'required|date',
            'lieu'=>'nullable|string'
        ]);

        $evenement->update($request->all());
        return response()->json($evenement);
    }

    public function destroy($id){
        $evenement = Evenement::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message'=>'Non autorisé'], 403);
        }

        $evenement->delete();
        return response()->json(['message'=>'Événement supprimé']);
    }

    public function inscrire($id)
    {
        $evenement = Evenement::findOrFail($id);
        $user = Auth::user();

        // Vérifie si l'utilisateur est déjà inscrit
        if ($evenement->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous êtes déjà inscrit'], 400);
        }

        // Inscription de l'utilisateur à l'événement
        $evenement->users()->attach($user->id);

        // Notification via le système Laravel avec target_id pour redirection
        $user->notify(new EvenementNotification($evenement, "Vous êtes inscrit à l'événement {$evenement->titre}"));

        // Notifier les admins de l'inscription
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new EvenementNotification(
                $evenement, 
                "{$user->name} s'est inscrit à l'événement {$evenement->titre}"
            ));
        }

        return response()->json(['message' => 'Inscription réussie']);
    }

    public function participants($id){
        $evenement = Evenement::with('users')->findOrFail($id);
        return response()->json($evenement->users);
    }

    // Récupérer les 3 prochains événements
    public function upcomingLimited() {
        $events = Evenement::orderBy('date', 'asc')
                           ->take(3)
                           ->get();
        return response()->json($events);
    }

    public function upcoming()
    {
        $evenements = Evenement::where('date', '>=', now())
                                ->orderBy('date', 'asc')
                                ->get();
        return response()->json($evenements);
    }
}
