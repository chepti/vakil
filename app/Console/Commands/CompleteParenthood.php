<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Relationship;
use App\Services\Family\RelationshipSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * השלמה חד-פעמית של קשרי הורות שחסרים בנתונים קיימים:
 * זוגות שבהם רק אחד מבני-הזוג רשום כהורה של הילדים.
 *
 * משתמש בדיוק באותם כללים שמרניים כמו ההשלמה האוטומטית במסכים
 * (ראו RelationshipSync) — מדלג על נישואים שניים, על ילדים שכבר יש
 * להם שני הורים, ועל שיוך ידני מפורש.
 */
class CompleteParenthood extends Command
{
    protected $signature = 'relationships:complete-parenthood {--dry : רק להציג מה היה נוסף, בלי לשנות}';

    protected $description = 'משלים הורות חסרה של בני-זוג לילדים הקיימים (הרצה חוזרת אינה משנה דבר)';

    public function handle(RelationshipSync $sync): int
    {
        $dry = (bool) $this->option('dry');

        $couples = Relationship::where('type', 'spouse')->get();
        $this->info("נסרקים {$couples->count()} קשרי זוגיות...");

        $created = [];

        foreach ($couples as $couple) {
            $a = Person::find($couple->person1_id);
            $b = Person::find($couple->person2_id);

            if (! $a || ! $b) {
                continue;
            }

            if ($dry) {
                // מריצים בתוך טרנזקציה שמתגלגלת אחורה — כך ה"יבש" משתמש
                // באותו קוד בדיוק, בלי לשכפל את הלוגיקה ובלי להשאיר עקבות
                DB::beginTransaction();
                $created = array_merge($created, $sync->completeParenthoodForCouple($a, $b));
                DB::rollBack();
            } else {
                $created = array_merge($created, $sync->completeParenthoodForCouple($a, $b));
            }
        }

        $created = array_values(array_unique($created));

        if (! $created) {
            $this->info('לא נמצאו קשרי הורות חסרים — אין מה להשלים.');

            return self::SUCCESS;
        }

        foreach ($created as $line) {
            $this->line('  • ' . $line);
        }

        $this->info(($dry ? 'היו נוספים ' : 'נוספו ') . count($created) . ' קשרי הורות.');

        if ($dry) {
            $this->warn('הרצה יבשה — לא בוצע שינוי. להרצה אמיתית: הריצו בלי --dry');
        }

        return self::SUCCESS;
    }
}
