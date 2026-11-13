<?php

namespace App\Mail;

use App\Models\Einstellung;
use App\Models\Rechnung;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Rechnung an einen Betrieb, mit dem PDF im Anhang.
 */
class RechnungMail extends Mailable
{
    public function __construct(public Rechnung $rechnung) {}

    public function envelope(): Envelope
    {
        $firma = (string) Einstellung::wert('firmenname', 'my-servicecenter');
        $antwort = Einstellung::wert('email');

        return new Envelope(
            from: new Address((string) config('mail.from.address'), $firma),
            replyTo: $antwort ? [new Address($antwort, $firma)] : [],
            subject: $this->rechnung->titel().' '.$this->rechnung->nummer.' – '.$firma,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.rechnung', with: [
            'firma' => (string) Einstellung::wert('firmenname', 'my-servicecenter'),
            'text' => Einstellung::wert('rechnung_mailtext'),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('rechnungen', $this->rechnung->pdf_pfad)
                ->as($this->rechnung->dateiname())
                ->withMime('application/pdf'),
        ];
    }
}
