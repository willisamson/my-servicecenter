<?php

namespace App\Mail;

use App\Models\Anfrage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Bestätigung an den Kunden mit seinem persönlichen Status-Link.
 */
class AnfrageBestaetigung extends Mailable
{
    public function __construct(
        public Anfrage $anfrage,
        public string $betriebName,
        public ?string $betriebEmail = null,
        public ?string $betriebTelefon = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $this->betriebName),
            replyTo: $this->betriebEmail ? [new Address($this->betriebEmail, $this->betriebName)] : [],
            subject: 'Ihre Anfrage bei '.$this->betriebName,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.anfrage-bestaetigung');
    }
}
