<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Contact extends Mailable
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
            ->subject('お問い合わせありがとうございます。')
            ->view('Mail.contact')
            ->with([
                'first_name' => $this->contact['first_name'],
                'last_name' => $this->contact['last_name'],
                'tel' => $this->contact['tel'],
                'mailaddress' => $this->contact['mailaddress'],
                'contact_type' => $this->contact['contact_type'],
                'contact_text' => $this->contact['contact_text'],
                'comment' => $this->contact['comment'],
            ]);
    }
}
