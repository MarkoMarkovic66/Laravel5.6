<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Signin extends Mailable
{
    use Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAILFROM_CONTACT'))
            ->subject('会員登録ありがとうございます。')
            ->view('Mail.signin')
            ->with([
                'first_name' => $this->contact['first_name'],
                'last_name' => $this->contact['last_name'],
                'mailaddress' => $this->contact['mailaddress'],
                'tel' => $this->contact['tel'],
            ]);
    }
}
