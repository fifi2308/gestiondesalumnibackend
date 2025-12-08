<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offre extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'type',
        'date_expiration'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

   public function postulants()
    {
        return $this->hasMany(Postulation::class, 'offre_id');
    }
}
