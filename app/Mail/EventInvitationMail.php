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

/**
 * הזמנה לאירוע — נשלחת ביד (כפתור "שלח הזמנה במייל" בעמוד האירוע), בשונה מ-NewEventMail
 * שנשלחת אוטומטית ליוצר האירוע. אותו עיצוב ותוכן, כותרת ונושא שמתאימים לשליחה מפורשת.
 */
class EventInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public ?string $recipientName = null,
        public ?string $recipientGender = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $label = $this->event->title ?: 'אירוע';

        return new Envelope(
            subject: "אירוע חדש: {$label} 💌",
        );
    }

    public function content(): Content
    {
        $hebrewDate = $this->event->hebrew_date
            ?: ($this->event->event_date ? HebrewDate::format(Carbon::parse($this->event->event_date)) : null);

        // "מיועד ל" — ענף היעד (אם הוגדר) + תגי קהל בשפה חופשית, כפי שהוגדרו באירוע עצמו.
        // מוצג בגוף המייל בלי קשר לקהל שאליו נשלחה ההרצה הנוכחית.
        $audienceParts = [];
        if ($this->event->audienceBranch) {
            $audienceParts[] = 'ענף ' . $this->event->audienceBranch->full_name;
        }
        foreach ($this->event->audience ?? [] as $tag) {
            $audienceParts[] = $tag;
        }

        // פתיחה מותאמת-מגדר, בלי לשון חיוב הגעה — "יש עדכון", לא "מוזמנים להגיע"
        $greetingWord = match ($this->recipientGender) {
            'female' => 'ברוכה הבאה',
            'male'   => 'ברוך הבא',
            default  => 'שלום',
        };
        $greeting = $greetingWord . ($this->recipientName ? ', ' . $this->recipientName : '');

        return new Content(
            view: 'emails.event-invitation',
            with: [
                'event'           => $this->event,
                'greeting'        => $greeting,
                'eventUrl'        => route('events.show', $this->event->id),
                'personName'      => $this->event->person?->full_name,
                'addedBy'         => $this->event->creator?->name,
                'hebrewDate'      => $hebrewDate,
                'gregDate'        => $this->event->event_date ? Carbon::parse($this->event->event_date)->format('d/m/Y') : null,
                'profileUrl'      => route('profile.edit'),
                'audienceParts'   => $audienceParts,
            ],
        );
    }

    /** מצרפת את תמונת ההזמנה כקובץ ממשי (בנוסף להטמעה בגוף המייל) — כדי שיהיה מה "לשמור/להעביר". */
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
