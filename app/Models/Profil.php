<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $fillable = [
        'user_id',
        'parcours_academique',
        'experiences_professionnelles',
        'competences',
        'realisations',
        'bio',
        'photo',
        'adresse'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
