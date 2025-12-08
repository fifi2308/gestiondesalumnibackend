<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log; // ✅ Import pour logs

class GenericNotification extends Notification
{
    use Queueable;

    protected $data;

    /**
     * ✅ ID auto-incrémenté, type entier (non UUID ici)
     */
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * ✅ Constructeur
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        // 🟩 Log au moment de la création de la notification
        Log::info('🆕 Création d’une nouvelle GenericNotification', [
            'data' => $data
        ]);
    }

    /**
     * ✅ Canaux utilisés
     */
    public function via($notifiable)
    {
        Log::info('📡 Canal de notification utilisé : database', [
            'user_id' => $notifiable->id ?? null
        ]);

        return ['database'];
    }

    /**
     * ✅ Données envoyées en base
     */
    public function toDatabase($notifiable)
    {
        $payload = [
            'message' => $this->data['message'] ?? 'Notification',
            'type' => $this->data['type'] ?? 'info',
             'target_id' => $this->data['target_id'] ?? null, // ✅ Ajout du target_id
            'extra' => $this->data['extra'] ?? null,
        ];

        Log::info('💾 Enregistrement notification dans la base', [
            'user_id' => $notifiable->id ?? null,
            'payload' => $payload
        ]);

        return $payload;
    }

    /**
     * (Optionnel) ✅ Format email
     */
    public function toMail($notifiable)
    {
        Log::info('📧 Envoi par mail de la notification', [
            'user_email' => $notifiable->email ?? null,
            'data' => $this->data
        ]);

        return (new MailMessage)
            ->subject('Nouvelle notification')
            ->line($this->data['message'] ?? 'Vous avez une nouvelle notification.')
            ->action('Voir maintenant', url('/'))
            ->line('Merci d’utiliser notre plateforme !');
    }

    /**
     * (Optionnel) ✅ Format pour broadcast (temps réel)
     */
    public function toArray($notifiable)
    {
        $arrayData = [
            'message' => $this->data['message'] ?? 'Notification',
            'type' => $this->data['type'] ?? 'info',
        ];

        Log::info('📢 Notification diffusée en temps réel', [
            'user_id' => $notifiable->id ?? null,
            'data' => $arrayData
        ]);

        return $arrayData;
    }
}
