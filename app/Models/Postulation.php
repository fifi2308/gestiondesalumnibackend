<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'offre_id',
        'user_id',
        'nom',
        'prenom',
        'email' ,
        'telephone' ,
        'cv' ,
        'message'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offre()
    {
        return $this->belongsTo(Offre::class, 'offer_id');
    }
}
