<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(){
        return User::all();
    }

    public function show($id){
        return User::findOrFail($id);
    }

    public function update(Request $request, $id){
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }

    public function destroy($id){
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message'=>'User deleted']);
    }

    // Récupérer le profil de l’utilisateur connecté

        public function profile(Request $request) {
    $user = $request->user();

    return response()->json([
        'user' => $user,
        'followers_count' => $user->followers()->count(),
        'following_count' => $user->following()->count(),
        'connections' => $user->following()->count(), // optionnel
        'posts_count' => $user->posts()->count() ?? 0
    ]);
}


    // Mettre à jour le profil de l’utilisateur connecté
    public function updateProfile(Request $request) {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed' // pour réinitialiser le mot de passe
        ]);

        $data = $request->only('name', 'email');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    // Mettre à jour un utilisateur par ID (pour admin)
    
    public function updateUser(Request $request, $id) {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }
    public function updatePassword(Request $request) {
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:6|confirmed',
    ]);

    $user = $request->user();

    if (!\Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'Mot de passe actuel incorrect'], 400);
    }

    $user->password = bcrypt($request->new_password);
    $user->save();

    return response()->json(['message' => 'Mot de passe mis à jour avec succès !']);
}

// Récupérer les suggestions d'utilisateurs (exclut l'utilisateur connecté et les admins)
public function suggestions() {
    $userId = auth()->id();
    $users = User::where('id', '!=', $userId)
                 ->where('role', '!=', 'admin')
                 ->get();
    return response()->json($users);
}

// Pour suivre un utilisateur
public function follow($id) {
    $user = auth()->user();
    $target = User::findOrFail($id);

    if (!$user->following()->where('follow_id', $id)->exists()) {
        $user->following()->attach($id);
        return response()->json(['message' => "Vous suivez {$target->name}"]);
    }

    return response()->json(['message' => "Vous suivez déjà {$target->name}"], 400);
}


// Pour récupérer les relations de suivi
public function unfollow($id) {
    $user = auth()->user();
    $target = User::findOrFail($id);

    if ($user->following()->where('follow_id', $id)->exists()) {
        $user->following()->detach($id);
        return response()->json(['message' => "Vous ne suivez plus {$target->name}"]);
    }

    return response()->json(['message' => "Vous ne suivez pas {$target->name}"], 400);
}

public function block($id)
{
    $user = User::find($id);
    if (!$user) return response()->json(['message' => 'Utilisateur non trouvé'], 404);

    $user->is_blocked = true;
    $user->save();

    return response()->json(['message' => 'Utilisateur bloqué avec succès']);
}

public function unblock($id)
{
    $user = User::find($id);
    if (!$user) return response()->json(['message' => 'Utilisateur non trouvé'], 404);

    $user->is_blocked = false;
    $user->save();

    return response()->json(['message' => 'Utilisateur débloqué avec succès']);
}



}
