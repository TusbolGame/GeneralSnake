<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $code;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $confirm_code)
    {
        //
        $this->user = $user;
        $this->code = $confirm_code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->user->reset_url = env('CLIENT_URL').'/reset_password/'.$this->code;
        $locale = App::getLocale();

        if ($locale != 'tr') $locale = 'en';
        return $this->to($this->user->email, $this->user->name)
            ->subject('Reset your password')
            ->view('emails.'.$locale.'.forgot')
            ->with([
                'user' => $this->user
            ]);
    }
}
