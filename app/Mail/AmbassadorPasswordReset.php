<?php

namespace App\Mail;

use App\Models\Ambassador;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AmbassadorPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ambassador $ambassador,
        public string $reference,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your TMG Ambassador sign-in details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ambassador-password-reset',
            text: 'mail.ambassador-password-reset-text',
        );
    }
}
