<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = ['actualite_id', 'user_id'];

    public function actualite()
    {
        return $this->belongsTo(Actualite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
