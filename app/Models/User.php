<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotificationBase;
use App\Models\Actualite;
use Illuminate\Support\Str;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

protected $appends = ['photo_url'];

    // Relations
    public function profil() {
        return $this->hasOne(Profil::class);
    }

    public function offres() {
        return $this->hasMany(Offre::class);
    }

    public function sentMessages() {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages() {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    
    public function evenements() {
    return $this->belongsToMany(Evenement::class, 'evenement_user')->withTimestamps();
}


    public function groupes() {
        return $this->belongsToMany(Groupe::class);
    }

    public function articles() {
        return $this->hasMany(Article::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
   
// Les personnes que l'utilisateur suit
    public function following() {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'follow_id');
    }

    // Les personnes qui suivent l'utilisateur
    public function followers() {
        return $this->belongsToMany(User::class, 'follows', 'follow_id', 'user_id');
    }

   public function actualites()
{
    return $this->hasMany(Actualite::class, 'user_id');
}

public function getFollowersCountAttribute()
{
    return $this->followers()->count();
}

public function getFollowingCountAttribute()
{
    return $this->following()->count();
}

// Ajoute l'attribut virtuel photo_url dans la sérialisation JSON

public function getPhotoUrlAttribute()
{
    // Priorité : colonne 'photo' sur user (si existante) puis profil.photo
    $photo = $this->photo ?? ($this->profil->photo ?? null);

    if (!$photo) {
        // URL d'avatar par défaut (ajuste selon ton projet)
        return asset('images/default-avatar.png'); 
    }

    // Si c'est déjà une URL complète, retourne tel quel
    if (Str::startsWith($photo, ['http://', 'https://'])) {
        return $photo;
    }

    // Sinon, suppose que le fichier est dans storage (storage/app/public/)
    return asset('storage/'.$photo);
}


}
