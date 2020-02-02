<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

class SMSMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $code;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $code)
    {
        //
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $locale = App::getLocale();
        $this->user->code = $this->code;

        if ($locale != 'tr') $locale = 'en';
        return $this->to($this->user->email, $this->user->name)
            ->subject('Confirm your account')
            ->view('emails.'.$locale.'.sms')
            ->with([
                'user' => $this->user
            ]);
    }
}
