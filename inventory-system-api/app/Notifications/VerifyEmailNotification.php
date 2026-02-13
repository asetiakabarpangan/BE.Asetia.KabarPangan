<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name');
        $verificationUrl = $this->verificationUrl($notifiable);
        $userName = $notifiable->name ?? 'Rekan Kabar Pangan';

        return (new MailMessage)
            ->subject("Verifikasi Akun Anda | {$appName}")
            ->greeting("Halo {$userName},")
            ->line("Selamat datang di **{$appName}**.")
            ->line('Platform internal Kabar Pangan untuk pengelolaan inventaris dan peminjaman aset perusahaan secara terintegrasi.')
            ->line('Untuk mulai menggunakan sistem ini, silakan lakukan verifikasi alamat email Anda dengan menekan tombol di bawah.')
            ->action('Verifikasi Email Sekarang', $verificationUrl)
            ->line('Link verifikasi ini **berlaku selama 60 menit** demi menjaga keamanan akun Anda.')
            ->line('Apabila Anda merasa tidak pernah mendaftar atau menerima email ini secara tidak sengaja, silakan abaikan pesan ini.')
            ->line('Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.')
            ->salutation("Hormat kami,\nTim {$appName}\nKabar Pangan");
    }
}
