<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MoneyTransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = $this->data['type'] === 'sent' 
            ? 'Money Transfer Sent Successfully' 
            : 'Money Received';
            
        return $this->subject($subject)
                    ->view('emails.money-transfer');
    }
}