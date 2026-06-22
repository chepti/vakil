<?php

namespace App\Http\Controllers;

use App\Mail\CustomAnnouncementMail;
use App\Mail\MonthlyDigestMail;
use App\Models\Document;
use App\Models\Invitation;
use App\Models\Person;
use App\Models\User;
use App\Services\DigestBuilder;
use App\Support\HebrewDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

class AdminController extends Controller
{
    /** כל המתודות כאן מוגנות לאדמין בלבד. */
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /**
     * פאנל הניהול הראשי.
     */
    public function index()
    {
        $this->ensureAdmin();

        $users = User::with('person:id,first_name,last_name')
            ->orderBy('created_at')
            ->get()
            ->map(fn($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'role'    => $u->role,
                'status'  => $u->status,
                'person'  => $u->person ? $u->person->full_name : null,
                'joined'  => $u->created_at?->format('d/m/Y'),
            ]);

        $invitations = Invitation::with(['invitedBy:id,name', 'person:id,first_name,last_name'])
            ->whereNull('used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($inv) => [
                'id'         => $inv->id,
                'email'      => $inv->email,
                'invited_by' => $inv->invitedBy?->name,
                'person'     => $inv->person ? $inv->person->full_name : null,
                'expires_at' => $inv->expires_at?->format('d/m/Y'),
                'expired'    => $inv->expires_at?->isPast() ?? true,
            ]);

        $missingBirthday = Person::whereNull('birth_date_gregorian')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'is_deceased'])
            ->map(fn($p) => [
                'id'          => $p->id,
                'full_name'   => $p->full_name,
                'is_deceased' => $p->is_deceased,
            ]);

        $missingPhoto = Person::whereNull('profile_photo')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn($p) => ['id' => $p->id, 'full_name' => $p->full_name]);

        $documents = Document::with('uploadedBy:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($d) => [
                'id'       => $d->id,
                'title'    => $d->title,
                'url'      => $d->url,
                'size'     => $d->size,
                'uploaded' => $d->created_at?->format('d/m/Y'),
            ]);

        $people = Person::orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => "{$p->first_name} {$p->last_name}"])
            ->all();

        return Inertia::render('Admin/Index', [
            'people'  => $people,
            'summary' => [
                'users_total'    => $users->count(),
                'users_pending'  => $users->where('status', 'pending')->count(),
                'invites_open'   => $invitations->count(),
                'invites_expired' => $invitations->where('expired', true)->count(),
                'people_total'   => Person::count(),
                'missing_bday'   => $missingBirthday->count(),
                'missing_photo'  => $missingPhoto->count(),
            ],
            'users'           => $users,
            'invitations'     => $invitations,
            'missingBirthday' => $missingBirthday,
            'missingPhoto'    => $missingPhoto,
            'documents'       => $documents,
        ]);
    }

    // ─── ניהול משתמשים ─────────────────────────────────────────────

    /** החלפת תפקיד אדמין/חבר. */
    public function toggleRole(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'אי אפשר לשנות את התפקיד של עצמך']);
        }

        $user->update(['role' => $user->role === 'admin' ? 'member' : 'admin']);

        return back()->with('success', "התפקיד של {$user->name} עודכן ל-" . ($user->role === 'admin' ? 'מנהל' : 'חבר'));
    }

    /** מחיקת משתמש (לא מוחק את הדמות בעץ). */
    public function deleteUser(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'אי אפשר למחוק את עצמך']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "המשתמש {$name} נמחק (הדמות בעץ נשארה)");
    }

    // ─── מסמכים ────────────────────────────────────────────────────

    public function uploadDocument(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'title'         => $request->title,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'המסמך הועלה בהצלחה');
    }

    public function deleteDocument(Document $document)
    {
        $this->ensureAdmin();

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('success', 'המסמך נמחק');
    }

    // ─── ייצוא CSV ─────────────────────────────────────────────────

    /** רשימת כל המשפחה — שם, מה עושה, עיר, מייל, טלפון, תאריך לידה. */
    public function exportFamily(): StreamedResponse
    {
        $this->ensureAdmin();

        $rows = Person::orderBy('last_name')->orderBy('first_name')->get()->map(fn($p) => [
            $p->full_name,
            $p->gender === 'female' ? 'נקבה' : ($p->gender === 'male' ? 'זכר' : ''),
            $p->current_occupation,
            $p->city,
            $p->email,
            $p->phone,
            $p->birth_date_gregorian?->format('d/m/Y'),
            $p->is_deceased ? 'ז"ל' : '',
        ]);

        return $this->csv('family-vakil.csv',
            ['שם מלא', 'מגדר', 'מה עושה כיום', 'עיר', 'מייל', 'טלפון', 'תאריך לידה', 'סטטוס'],
            $rows
        );
    }

    /** רשימת משתמשי האתר. */
    public function exportUsers(): StreamedResponse
    {
        $this->ensureAdmin();

        $rows = User::orderBy('name')->get()->map(fn($u) => [
            $u->name,
            $u->email,
            $u->role === 'admin' ? 'מנהל' : 'חבר',
            $u->status === 'active' ? 'פעיל' : 'ממתין',
            $u->created_at?->format('d/m/Y'),
        ]);

        return $this->csv('users-vakil.csv',
            ['שם', 'מייל', 'תפקיד', 'סטטוס', 'הצטרף'],
            $rows
        );
    }

    /** ימי הולדת של כל השנה, ממוין לפי חודש ויום. */
    public function exportBirthdays(): StreamedResponse
    {
        $this->ensureAdmin();

        $rows = Person::whereNotNull('birth_date_gregorian')
            ->where('is_deceased', false)
            ->get()
            ->sortBy(fn($p) => $p->birth_date_gregorian->format('m-d'))
            ->map(fn($p) => [
                $p->birth_date_gregorian->format('d/m'),
                $p->full_name,
                $p->birth_date_hebrew,
                $p->birth_date_gregorian->year,
            ]);

        return $this->csv('birthdays-vakil.csv',
            ['תאריך', 'שם', 'תאריך עברי', 'שנת לידה'],
            $rows
        );
    }

    // ─── דיגסט מייל ────────────────────────────────────────────────

    /** שליחת תצוגה מקדימה של העדכון החודשי לאדמין עצמו בלבד. */
    public function digestPreview(Request $request, DigestBuilder $builder)
    {
        $this->ensureAdmin();

        $when   = Carbon::today();
        $data   = $builder->build($when);
        $branch = null;

        if ($request->user()->digest_branch_person_id) {
            $root   = Person::find($request->user()->digest_branch_person_id);
            $branch = $root ? $builder->branchSection($root, $when) : null;
        }

        Mail::to($request->user()->email)
            ->send(new MonthlyDigestMail($data, $request->user()->name, $branch));

        return back()->with('digest_success', 'תצוגה מקדימה נשלחה לכתובת ' . $request->user()->email);
    }

    /** שליחת העדכון החודשי לכל המשתמשים הרשומים. */
    public function digestSendAll(Request $request, DigestBuilder $builder)
    {
        $this->ensureAdmin();

        $when   = Carbon::today();
        $data   = $builder->build($when);

        $recipients = User::where('notify_monthly_digest', true)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        $branchCache = [];
        $sent = $failed = 0;

        foreach ($recipients as $user) {
            try {
                $branch = null;
                if ($user->digest_branch_person_id) {
                    $bid = $user->digest_branch_person_id;
                    if (! array_key_exists($bid, $branchCache)) {
                        $root = Person::find($bid);
                        $branchCache[$bid] = $root ? $builder->branchSection($root, $when) : null;
                    }
                    $branch = $branchCache[$bid];
                }
                Mail::to($user->email)->send(new MonthlyDigestMail($data, $user->name, $branch));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $msg = "נשלחו {$sent} מיילים" . ($failed ? " ({$failed} נכשלו)" : '') . '.';
        return back()->with('digest_success', $msg);
    }

    /** שליחת הודעה מותאמת (נושא + גוף חופשי) לקבוצת יעד. */
    public function sendCustom(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'subject'          => ['required', 'string', 'max:200'],
            'body'             => ['required', 'string', 'max:6000'],
            'branch_person_id' => ['nullable', 'integer', 'exists:people,id'],
        ]);

        $query = User::where('status', 'active')->whereNotNull('email');

        if ($data['branch_person_id']) {
            $root         = Person::findOrFail($data['branch_person_id']);
            $ids          = $root->descendantIds();
            $ids[]        = $root->id;
            $query->whereIn('person_id', $ids);
        }

        $recipients = $query->get();
        $sent = $failed = 0;

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new CustomAnnouncementMail(
                    $data['subject'],
                    $data['body'],
                    $user->name,
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $msg = "נשלחו {$sent} מיילים" . ($failed ? " ({$failed} נכשלו)" : '') . '.';
        return back()->with('digest_success', $msg);
    }

    /** בונה תגובת CSV עם BOM (לתמיכת עברית באקסל). */
    private function csv(string $filename, array $header, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
