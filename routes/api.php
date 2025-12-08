<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfilController;
use App\Http\Controllers\Api\OffreController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\GroupeController;
use App\Http\Controllers\Api\PostulationController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Api\DiscussionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\ActualiteController;
use App\Http\Controllers\Api\PostulerController; // si tu utilises ce contrôleur

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

// Mot de passe
Route::post('/forget-password', [ForgetPasswordController::class, 'sendResetLink']);
Route::post('/reset-password/{token}', [ForgetPasswordController::class, 'resetPassword'])
     ->name('password.reset');

// Suggestions


// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Routes protégées par Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

Route::get('/suggestions', [SuggestionController::class, 'index']);
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Utilisateurs
    Route::apiResource('users', UserController::class);

   // Profil
Route::get('/profil', [ProfilController::class, 'index']);
Route::post('/profil/store', [ProfilController::class, 'store']);       // Création
Route::post('/profil/update', [ProfilController::class, 'update']);    // Mise à jour avec _method=PUT
Route::delete('/profil', [ProfilController::class, 'destroy']);
Route::get('/profil/user', [ProfilController::class, 'getUserProfil']);
Route::get('/profil/{id}/followers', [ProfilController::class, 'getFollowers']);
Route::get('/profil/{id}/following', [ProfilController::class, 'getFollowing']);


    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{userId}', [MessageController::class, 'getMessagesWithUser']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::put('/messages/{id}', [MessageController::class, 'update']);  // ✅ Modifier
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']); // ✅ Supprimer
    Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::get('/messages/unread', [MessageController::class, 'unread']);
    Route::get('/conversations', [MessageController::class, 'getConversations']);

    // Offres
     Route::post('/offres/{id}/postuler', [OffreController::class, 'postuler']);
    Route::get('/offres/recent', [OffreController::class, 'recent']);
    Route::apiResource('offres', OffreController::class)->only(['index','show','store','update','destroy']);
    Route::get('/offres/{id}/postulations', [OffreController::class, 'getPostulations']);

   

    // Postulations
    Route::apiResource('postulations', PostulationController::class)->only(['index','store','show','destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/paginated', [NotificationController::class, 'paginated']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);


    // Evenements
    Route::get('/evenements/upcoming', [EvenementController::class, 'upcoming']);
    Route::get('/evenements/recent', [EvenementController::class, 'upcomingLimited']);
    Route::apiResource('evenements', EvenementController::class)->only(['index','show']); // CRUD admin séparé
    Route::post('/evenements/{id}/inscrire', [EvenementController::class, 'inscrire']);
    Route::get('/evenements/{id}/participants', [EvenementController::class, 'participants']);
    

    // Groupes et discussions
    // Groupes et discussions
// ✅ ROUTES SPÉCIALES EN PREMIER (avant apiResource)
Route::get('/groupes/populaires', [GroupeController::class, 'populaires']);
Route::get('/groupes/user-groups', [GroupeController::class, 'getUserGroups']);

// ✅ ROUTES AVEC PARAMÈTRES SPÉCIFIQUES
Route::get('/groupes/{id}/discussions', [GroupeController::class, 'discussions']);
Route::post('/groupes/{id}/discussions', [GroupeController::class, 'postDiscussion']);
Route::get('/groupes/{id}/members', [GroupeController::class, 'members']);
Route::post('/groupes/{id}/join', [GroupeController::class, 'join']);
Route::delete('/groupes/{id}/quit', [GroupeController::class, 'quit']);
Route::delete('/groupes/{groupe_id}/remove-user/{user_id}', [GroupeController::class, 'removeUser']);
Route::post('/groupes/{group}/add-user', [GroupeController::class, 'addUser']);

// ✅ apiResource EN DERNIER (pour index, show, store, update, destroy)
Route::apiResource('groupes', GroupeController::class);

    Route::put('/discussions/{id}', [GroupeController::class, 'updateDiscussion']);
    Route::delete('/discussions/{id}', [GroupeController::class, 'deleteDiscussion']);

    // Actualités
    Route::get('/actualites/search', [ActualiteController::class, 'search']);
    Route::apiResource('actualites', ActualiteController::class);
    Route::post('actualites/{id}/commenter', [ActualiteController::class, 'commenter']);
    Route::post('actualites/{id}/like', [ActualiteController::class, 'toggleLike']);
    Route::get('/mes-actualites', [ActualiteController::class, 'getMesActualites']);
    Route::get('/actualites/user/{id}', [ActualiteController::class, 'getByUser']);
    
    Route::post('/follow/{id}', [FollowController::class, 'toggleFollow']);
    Route::get('/all-users', [SuggestionController::class, 'allUsers']);

    Route::post('/users/{id}/block', [UserController::class, 'block']);
    Route::post('/users/{id}/unblock', [UserController::class, 'unblock']);

    Route::delete('/groupes/{groupe_id}/remove-user/{user_id}', [GroupeController::class, 'removeUser']);

   Route::get('/groupes/{id}/members', [GroupeController::class, 'members']);
 Route::get('/profil/{id}', [ProfilController::class, 'showPublic']);
// API pour ajouter un utilisateur à un groupe
Route::post('/groupes/{group}/add-user', [GroupeController::class, 'addUser']);



//Route::get('/groupes/user-groups', [GroupeController::class, 'userGroups']);


    
});




/*
|--------------------------------------------------------------------------
| Routes admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','admin'])->group(function () {

    // Dashboard stats
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // CRUD Evenements
    Route::post('/evenements', [EvenementController::class, 'store']);
    Route::put('/evenements/{id}', [EvenementController::class, 'update']);
    Route::delete('/evenements/{id}', [EvenementController::class, 'destroy']);
});
