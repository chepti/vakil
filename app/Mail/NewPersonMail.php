<?php

namespace App\Mail;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPersonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Person $person,
        public ?string $recipientName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "דמות חדשה נוספה לעץ: {$this->person->full_name} 🌳",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-person',
            with: [
                'person'        => $this->person,
                'recipientName' => $this->recipientName,
                'personUrl'     => route('people.show', $this->person->id),
                'addedBy'       => $this->person->createdBy?->name,
                'profileUrl'    => route('profile.edit'),
            ],
        );
    }
}
