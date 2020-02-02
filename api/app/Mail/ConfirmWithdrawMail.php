<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

class ConfirmWithdrawMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $code;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $confirm_code, $coin, $amount, $address) {
        //
        $this->user = $user;
        $this->confirm_code = $confirm_code;
        $this->coin = $coin;
        $this->amount = $amount;
        $this->address = $address;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $this->user->confirm_url = env('APP_URL').'/api/confirm/withdraw/'.$this->confirm_code;
        $this->user->coin = $this->coin;
        $this->user->amount= $this->amount;
        $this->user->address = $this->address;
        $locale = App::getLocale();

        if ($locale != 'tr') $locale = 'en';
        return $this->to($this->user->email, $this->user->name)
            ->subject('Confirm your withdraw')
            ->view('emails.'.$locale.'.confirm_withdraw')
            ->with([
                'user' => $this->user
            ]);
    }
}
