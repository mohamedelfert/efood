<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerifyOtp extends Mailable
{
    use Queueable, SerializesModels;

    private $otp;
    private $localization;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp, $localization)
    {
        $this->otp = $otp;
        $this->localization = $localization;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $view = $this->localization === 'ar' ? 'emails.email_verify_otp_ar' : 'emails.email_verify_otp_en';

        return $this->view($view)
            ->subject('Your OTP for Email verification')
            ->with([
                'otp' => $this->otp,
            ]);
    }
}