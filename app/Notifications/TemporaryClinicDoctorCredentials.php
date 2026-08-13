<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent when HR creates a temporary clinic doctor account, or resets its
 * password. No web login link — this account is mobile-app-only.
 */
class TemporaryClinicDoctorCredentials extends Notification
{
    use Queueable;

    protected $password;

    public function __construct($plainPassword)
    {
        $this->password = $plainPassword;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Clinic Module Login — ' . ($notifiable->resort->resort_name ?? 'HRVMS'))
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('You have been given access to the Clinic module on the HR mobile app.')
            ->line('Email: ' . $notifiable->email)
            ->line('Password: ' . $this->password)
            ->line('Log in from the mobile app using these credentials.')
            ->line('Please keep these credentials confidential.');
    }
}
