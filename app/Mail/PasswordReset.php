<?php
/**
 * Class Email ResetPassword
 */
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Config;

/**
 * PasswordReset
 */
class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * users
     *
     * @var [Object]
     */
    private $_user;


    /**
     * Create a new message instance.
     * 
     * @param String $token Token
     * @param String $email Email
     * 
     * @return void
     */
    public function __construct($user)
    {
        $this->_user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("パスワード再設定のご連絡")
            ->view(
                'emails.resetPassword',
                [
                'fullname' => $this->_user->first_name . ' ' . $this->_user->last_name,
                'url' => Config::get('app.frontend_url') . '/resetPassword/' . $this->_user->remember_token,
                ]
            );
    }
}
