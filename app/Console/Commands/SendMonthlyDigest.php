<?php

namespace App\Console\Commands;

use App\Mail\MonthlyDigestMail;
use App\Models\User;
use App\Services\DigestBuilder;
use App\Support\HebrewDate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyDigest extends Command
{
    protected $signature = 'digest:monthly
        {--force : שליחה גם אם היום אינו ראש חודש}
        {--date= : תאריך לועזי לבדיקה (YYYY-MM-DD) במקום היום}
        {--dry : בנייה והצגה בלבד, ללא שליחת מיילים}
        {--to= : שליחה לכתובת מייל ספציפית (לבדיקה) במקום לכל הרשומים}';

    protected $description = 'שולח את המייל החודשי (ראש חודש) לכל הרשומים שבחרו לקבלו';

    public function handle(DigestBuilder $builder): int
    {
        $when = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        [$sendToday, $effectiveWhen] = $this->resolveSendDate($when);

        if (! $this->option('force') && ! $sendToday) {
            $this->info('היום אינו ראש חודש (' . HebrewDate::format($when) . ') — לא נשלח דבר. להרצה כפויה: --force');

            return self::SUCCESS;
        }

        $when = $effectiveWhen;
        $data = $builder->build($when);
        $this->info("מכין מייל לחודש {$data['monthName']} {$data['yearGematria']}:");
        $this->line('  תינוקות: ' . count($data['newBabies'])
            . ' | אירועים: ' . count($data['events'])
            . ' | ימי הולדת עגולים: ' . count($data['roundBirthdays'])
            . ' | ימי נישואין עגולים: ' . count($data['roundAnniversaries']));

        if ($this->option('dry')) {
            $this->warn('--dry: לא נשלחו מיילים.');

            return self::SUCCESS;
        }

        // --to שולח רק לכתובת מבדיקה אחת (ללא סינון notify)
        if ($toEmail = $this->option('to')) {
            $user = User::where('email', $toEmail)->first();
            $branch = null;
            if ($user?->digest_branch_person_id) {
                $root   = \App\Models\Person::find($user->digest_branch_person_id);
                $branch = $root ? $builder->branchSection($root, $when) : null;
            }
            Mail::to($toEmail)->send(new MonthlyDigestMail($data, $user?->name, $branch));
            $this->info("נשלח בדיקה אל {$toEmail}.");
            return self::SUCCESS;
        }

        $recipients = User::query()
            ->where('notify_monthly_digest', true)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        // סעיף הענף מחושב פעם אחת לכל דמות-ענף נבחרת (משותף בין משתמשים שבחרו אותה דמות)
        $branchCache = [];

        $sent = 0;
        $failed = 0;
        foreach ($recipients as $user) {
            try {
                $branch = null;
                if ($user->digest_branch_person_id) {
                    $bid = $user->digest_branch_person_id;
                    if (! array_key_exists($bid, $branchCache)) {
                        $root = \App\Models\Person::find($bid);
                        $branchCache[$bid] = $root ? $builder->branchSection($root, $when) : null;
                    }
                    $branch = $branchCache[$bid];
                }

                Mail::to($user->email)->send(new MonthlyDigestMail($data, $user->name, $branch));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("נכשל ל-{$user->email}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("נשלחו {$sent} מיילים" . ($failed ? " ({$failed} נכשלו)" : '') . '.');

        return self::SUCCESS;
    }

    /**
     * קובע אם לשלוח היום, ואת התאריך ה"אפקטיבי" לבניית התוכן.
     *
     * בכל ראש חודש רגיל — שולחים באותו יום.
     * בראש חודש תשרי (ראש השנה) — לא שולחים בחג עצמו, אלא יום לפני
     * (כ״ט באלול, ערב ראש השנה), עם תוכן שמתאר את תשרי כאילו זה 1 בתשרי.
     *
     * @return array{0:bool, 1:Carbon} [האם לשלוח היום, התאריך לבניית התוכן]
     */
    private function resolveSendDate(Carbon $when): array
    {
        $parts = HebrewDate::parts($when);

        // ראש חודש רגיל (לא תשרי) — שולחים היום, כרגיל
        if ($parts['day'] === 1 && $parts['month'] !== 1) {
            return [true, $when];
        }

        // כ״ט באלול — מחר הוא א׳ בתשרי (ראש השנה): שולחים היום עם תוכן של תשרי
        $tomorrow = $when->copy()->addDay();
        $tomorrowParts = HebrewDate::parts($tomorrow);
        if ($tomorrowParts['day'] === 1 && $tomorrowParts['month'] === 1) {
            return [true, $tomorrow];
        }

        return [false, $when];
    }
}
