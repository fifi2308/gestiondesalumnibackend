<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    // La clé primaire n'est pas auto-incrémentée
    public $incrementing = false;

    // La clé primaire est de type string
    protected $keyType = 'string';

    
    // Les champs qui peuvent être assignés en masse
    protected $fillable = [
        'type',
        'data',
        'read_at',
        'notifiable_id',
        'notifiable_type'
    ];

    // Optionnel : si tu ne veux pas utiliser les timestamps
    // public $timestamps = false;

    /**
     * Boot the model.
     * Génère automatiquement un UUID lors de la création.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    

    /**
     * Définition de la relation polymorphique (exemple pour notification)
     */
    public function notifiable()
    {
        return $this->morphTo();
    }
}
