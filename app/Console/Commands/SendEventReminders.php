<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Evenement;
use App\Models\Notification;
use Carbon\Carbon;

class SendEventReminders extends Command
{
    protected $signature = 'events:remind';
    protected $description = 'Envoyer des notifications de rappel pour les événements';

    public function handle()
    {
        $tomorrow = Carbon::tomorrow();

        $evenements = Evenement::with('users')
            ->whereDate('date', $tomorrow)
            ->get();

        foreach($evenements as $evenement){
            foreach($evenement->users as $user){
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'evenement',
                    'contenu' => "Rappel : l'événement '{$evenement->titre}' est prévu demain."
                ]);
            }
        }

        $this->info('Rappels envoyés avec succès.');
    }
}
