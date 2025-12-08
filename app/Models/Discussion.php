<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Groupe;

class Discussion extends Model
{
    protected $fillable = ['group_id', 'user_id', 'message', 'file_path'];


    public function groupe() {
        return $this->belongsTo(Groupe::class, 'group_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
