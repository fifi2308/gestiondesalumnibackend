<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    protected $fillable = [
        'titre',
        'description',
        'date',
        'lieu'
    ];

    public function users() {
    return $this->belongsToMany(User::class, 'evenement_user')->withTimestamps();
}


}
