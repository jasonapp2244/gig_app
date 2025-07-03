<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name, $otp;

    public function __construct($name, $otp)
    {
        $this->name = $name;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your OTP Code')
                    ->view('mails.otp');
    }
}
