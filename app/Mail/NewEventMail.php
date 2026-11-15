<?php

namespace App\Mail;

use App\Models\Event;
use App\Support\HebrewDate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NewEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public ?string $recipientName = null,
        public ?int $recipientId = null,
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
                'blessUrls'   => $this->blessUrls(),
                'profileUrl'  => route('profile.edit'),
            ],
        );
    }

    /** קישורים חתומים לברכה בלחיצה אחת (ללא התחברות); ריק אם אין נמען מזוהה */
    private function blessUrls(): array
    {
        if (! $this->recipientId) return [];
        $make = fn ($kind) => \Illuminate\Support\Facades\URL::signedRoute(
            'events.quick-blessing', ['event' => $this->event->id, 'user' => $this->recipientId, 'kind' => $kind]
        );
        return ['mazal_tov' => $make('mazal_tov'), 'shefa' => $make('shefa')];
    }

    /** מצרפת את תמונת ההזמנה כקובץ ממשי (בנוסף להטמעה בגוף המייל). */
    public function attachments(): array
    {
        if (! $this->event->invitation_image) return [];

        $disk = Storage::disk('public');
        if (! $disk->exists($this->event->invitation_image)) return [];

        $label = $this->event->title ?: 'אירוע';
        $ext   = pathinfo($this->event->invitation_image, PATHINFO_EXTENSION) ?: 'jpg';

        return [
            Attachment::fromPath($disk->path($this->event->invitation_image))
                ->as("הזמנה-{$label}.{$ext}"),
        ];
    }
}
