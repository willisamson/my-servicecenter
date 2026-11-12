<?php

namespace App\Mail;

use App\Models\Anfrage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Benachrichtigung an den Betrieb: Es ist eine neue Anfrage eingegangen.
 */
class NeueAnfrageAnBetrieb extends Mailable
{
    public function __construct(
        public Anfrage $anfrage,
        public string $betriebName,
        public string $dashboardUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Anfrage: '.$this->anfrage->anliegenLabel().' – '.$this->anfrage->name,
            replyTo: $this->anfrage->email ? [new Address($this->anfrage->email, $this->anfrage->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.neue-anfrage');
    }
}
