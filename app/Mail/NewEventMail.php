<?php

namespace App\Mail;

use App\Models\Event;
use App\Support\HebrewDate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public ?string $recipientName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $label = $this->event->title ?: 'אירוע חדש';

        return new Envelope(
            subject: "אירוע חדש בעץ: {$label} 📅",
        );
    }

    public function content(): Content
    {
        $hebrewDate = $this->event->hebrew_date
            ?: ($this->event->event_date ? HebrewDate::format(Carbon::parse($this->event->event_date)) : null);

        return new Content(
            view: 'emails.new-event',
            with: [
                'event'       => $this->event,
                'recipientName' => $this->recipientName,
                'eventUrl'    => route('events.show', $this->event->id),
                'personName'  => $this->event->person?->full_name,
                'addedBy'     => $this->event->creator?->name,
                'hebrewDate'  => $hebrewDate,
                'gregDate'    => $this->event->event_date ? Carbon::parse($this->event->event_date)->format('d/m/Y') : null,
                'profileUrl'  => route('profile.edit'),
            ],
        );
    }
}
