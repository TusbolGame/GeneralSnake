<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

class VerifyAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        //
        $this->user= $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->user->confirmation_url = env('APP_URL').'/api/account/verify/'.$this->user->confirmation_code;
        $locale = App::getLocale();

        if ($locale != 'tr') $locale = 'en';
        return $this->to($this->user->email, $this->user->name)
            ->subject('Hesap Aktivasyonu')
            ->view('emails.'.$locale.'.verify')
            ->with([
                'user' => $this->user
            ]);
    }
}
