<?php

namespace App\Mail;

use App\Models\VoterRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AmbassadorWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VoterRecord $record) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to TMG — your Ambassador reference is '.$this->record->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ambassador-welcome',
            text: 'mail.ambassador-welcome-text',
        );
    }
}
