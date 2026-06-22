<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $data  פלט DigestBuilder::build()
     */
    public function __construct(
        public array $data,
        public ?string $recipientName = null,
        public ?array $branch = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "חודש טוב — {$this->data['monthName']} {$this->data['yearGematria']} 🌳",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-digest',
            with: [
                'd'             => $this->data,
                'recipientName' => $this->recipientName,
                'branch'        => $this->branch,
                'profileUrl'    => route('profile.edit'),
            ],
        );
    }
}
