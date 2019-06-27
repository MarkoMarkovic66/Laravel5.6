<?php
/**
 * Class Email ResetPassword
 */
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Enums\InquiryType;
use Config;

/**
 * PasswordReset
 */
class PutInquiry extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * users
     *
     * @var [Object]
     */
    private $_content;


    /**
     * Create a new message instance.
     * 
     * @param String $token Token
     * @param String $email Email
     * 
     * @return void
     */
    public function __construct($content)
    {
        $this->_content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $inquiryType = $this->_content['inquiry_type'];
        $inquiry_type_title = '';
        switch ($inquiryType) {
        case InquiryType::INQUIRY_TYPE_1_ID:
            $inquiry_type_title = InquiryType::INQUIRY_TYPE_1_MSG;
            break;
        case InquiryType::INQUIRY_TYPE_2_ID:
            $inquiry_type_title = InquiryType::INQUIRY_TYPE_2_MSG;
            break;
        case InquiryType::INQUIRY_TYPE_3_ID:
            $inquiry_type_title = InquiryType::INQUIRY_TYPE_3_MSG;
            break;
        case InquiryType::INQUIRY_TYPE_4_ID:
            $inquiry_type_title = InquiryType::INQUIRY_TYPE_4_MSG;
            break;
        case InquiryType::INQUIRY_TYPE_5_ID:
            $inquiry_type_title = InquiryType::INQUIRY_TYPE_5_MSG;
            break;
        }
        return $this->subject('【ALUGO】お問い合わせを承りました')
            ->view(
                'emails.putInquiry',
                [
                    'name' => $this->_content['member_name'],
                    'email' => $this->_content['email'],
                    'phone' => $this->_content['phone'],
                    'inquiry_type' => $inquiry_type_title,
                    'content_message' => $this->_content['question'],
                ]
            );
    }
}
