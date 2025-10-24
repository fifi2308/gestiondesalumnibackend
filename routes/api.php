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



  Route::post('/forget-password', [ForgetPasswordController::class, 'sendResetLink']);
Route::post('/reset-password/{token}', [ForgetPasswordController::class, 'resetPassword'])
     ->name('password.reset');



// -----------------------------
// 🔑 Authentification publique
// -----------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// -----------------------------
// 🛡️ Routes protégées par Sanctum
// -----------------------------
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);
    //Route::apiResource('profils', ProfilController::class);
    //Route::apiResource('offres', OffreController::class);
    
    Route::apiResource('evenements', EvenementController::class);
    Route::apiResource('groupes', GroupeController::class);
    Route::apiResource('articles', ArticleController::class);
     Route::get('/profil', [ProfilController::class, 'index']);        // GET -> récupère le profil
    Route::post('/profil', [ProfilController::class, 'store']);       // POST -> créer
    Route::put('/profil', [ProfilController::class, 'update']);       // PUT -> update
    Route::delete('/profil', [ProfilController::class, 'destroy']); 
      Route::get('/messages', [MessageController::class, 'index']);
      Route::get('/messages/{userId}', [MessageController::class, 'getMessagesWithUser']);
  Route::get('/offres', [OffreController::class,'index']);
    Route::get('/offres/{id}', [OffreController::class,'show']);
    Route::post('/offres', [OffreController::class,'store']);
    Route::post('/offres/{id}/postuler', [OffreController::class,'postuler']);
    Route::delete('/offres/{id}', [OffreController::class,'destroy']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::get('/conversations', [MessageController::class, 'getConversations']);
     Route::get('/postulations', [PostulationController::class, 'index']);
    Route::post('/postulations', [PostulationController::class, 'store']);
    Route::get('/postulations/{id}', [PostulationController::class, 'show']);
    Route::delete('/postulations/{id}', [PostulationController::class, 'destroy']);
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    // Messages non lus
Route::get('/messages/unread', [MessageController::class, 'unread']);

// Notifications non lues
Route::get('/notifications/unread', [NotificationController::class, 'unread']);

});
