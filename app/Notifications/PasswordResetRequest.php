<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
class PasswordResetRequest extends Notification implements ShouldQueue
{
    use Queueable;
    protected $token;


    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url('/api/v1/password/find/'.$this->token);
        return (new MailMessage)
//            ->line('You are receiving this email because we received a password reset request for your account.')
//            ->action('Reset Password', url($url))
//            ->line('If you did not request a password reset, no further action is required.');


        ->subject('ریست کردن پسورد مربی من')
        ->line('شما این ایمیل را دریافت کردید بخاطر اینکه از ما درخواست بازیابی گذرواژه برای حساب کاربری برای خود کرده اید .')
        ->action('ریست پسورد', url(config('app.url').route('password.reset', $this->token, false)))
        ->line('اگر شما درخواست ریست پسورد نکردید پس لازم نیست هیچ اقدامی انجام بدید');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
