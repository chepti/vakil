<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'הוזמנת להצטרף לאתר משפחת ואקיל 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'inviterName' => $this->invitation->invitedBy->name,
                'personName'  => $this->invitation->person?->full_name,
                'registerUrl' => route('invite.show', $this->invitation->token),
                'expiresAt'   => $this->invitation->expires_at->format('d/m/Y'),
            ],
        );
    }
}
