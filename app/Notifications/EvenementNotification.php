<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Evenement;

class EvenementNotification extends Notification
{
    use Queueable;

    protected $evenement;

    /**
     * Crée une nouvelle instance de notification.
     */
    public function __construct(Evenement $evenement)
    {
        $this->evenement = $evenement;
    }

    /**
     * Détermine les canaux de notification.
     */
    public function via($notifiable)
    {
        // Ici, on envoie la notification uniquement dans la base de données
        return ['database'];
    }

    /**
     * Contenu pour la notification en base de données.
     */
    public function toDatabase($notifiable)
    {
        return [
            'titre' => $this->evenement->titre,
            'message' => "Vous êtes inscrit à l'événement : {$this->evenement->titre}",
            'evenement_id' => $this->evenement->id,
        ];
    }

    /**
     * Optionnel : contenu pour email si tu veux envoyer aussi un mail.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Inscription à un événement')
                    ->line("Vous êtes inscrit à l'événement : {$this->evenement->titre}")
                    ->action('Voir l’événement', url("/evenements/{$this->evenement->id}"));
    }
}
