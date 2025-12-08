<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class Groupe extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'user_id'
    ];

   

    public function membres()
{
    return $this->belongsToMany(User::class, 'groupe_user', 'groupe_id', 'user_id');
}




    /*public function createur() {
        return $this->belongsTo(User::class, 'user_id');
    }*/
        public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}


    public function bannis()
{
    return $this->belongsToMany(User::class, 'groupe_bans', 'groupe_id', 'user_id');
}

}
