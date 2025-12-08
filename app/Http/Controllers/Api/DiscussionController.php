<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discussion;

class DiscussionController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        $discussion = Discussion::findOrFail($id);

        // Vérifie que l'utilisateur est l'auteur
        if ($discussion->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $discussion->update(['message' => $request->message]);
        return response()->json($discussion);
    }

    public function destroy($id)
    {
        $discussion = Discussion::findOrFail($id);

        // Vérifie que l'utilisateur est l'auteur
        if ($discussion->user_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $discussion->delete();
        return response()->json(['message' => 'Message supprimé']);
    }
}
