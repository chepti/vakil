<?php

namespace Database\Seeders;

use App\Models\Blessing;
use App\Models\CityLocation;
use App\Models\Event;
use App\Models\EventReaction;
use App\Models\FamilyPhoto;
use App\Models\GameStat;
use App\Models\MazalTov;
use App\Models\Message;
use App\Models\NameStory;
use App\Models\Person;
use App\Models\PhotoTag;
use App\Models\Recipe;
use App\Models\RecipeAdaptation;
use App\Models\RecipeComment;
use App\Models\Relationship;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * זריעת אתר ההדגמה — משפחת "ישראלי" הבדויה.
 * כל הדמויות, התאריכים, המתכונים והסיפורים כאן מומצאים לחלוטין.
 * מיועד לרוץ רק כש-APP_DEMO=true (ראו פקודת demo:reset).
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private int $avatarIndex = 0;

    public function run(): void
    {
        // ── משתמשים ───────────────────────────────────────────────
        $admin = User::create([
            'name'                  => 'מנהל ההדגמה',
            'email'                 => 'admin@example.com',
            'password'              => Hash::make('VakilDemo2026!'),
            'role'                  => 'admin',
            'status'                => 'active',
            'email_verified_at'     => now(),
            'notify_monthly_digest' => false,
        ]);

        $guest = User::create([
            'name'                  => 'אורח/ת בהדגמה',
            'email'                 => 'guest@example.com',
            'password'              => Hash::make('demo1234'),
            'role'                  => 'member',
            'status'                => 'active',
            'email_verified_at'     => now(),
            'notify_monthly_digest' => false,
        ]);

        $uid = $admin->id;

        // ── אנשים — 4 דורות של משפחת ישראלי הבדויה ────────────────
        // דור 0 — סבא-רבא וסבתא-רבתא (נפטרו)
        $avraham = $this->person($uid, 'אברהם', 'ישראלי', 'male', '1922-03-15', 'ט"ו באדר תרפ"ב', [
            'is_deceased' => true, 'death_date_gregorian' => '1998-01-20', 'death_date_hebrew' => 'כ"ב בטבת תשנ"ח',
            'bio' => 'עלה ארצה בילדותו, היה נגר אומן ובנה במו ידיו את רהיטי הבית. אהב לספר סיפורים ליד מדורת ל"ג בעומר.',
        ]);
        $tamarSavta = $this->person($uid, 'תמר', 'ישראלי', 'female', '1926-07-01', 'י"ט בתמוז תרפ"ו', [
            'maiden_name' => 'ברקת', 'is_deceased' => true,
            'death_date_gregorian' => '2011-11-08', 'death_date_hebrew' => 'י"א בחשוון תשע"ב',
            'bio' => 'תופרת מחוננת שכל שמלות הכלה במשפחה עברו דרך הידיים שלה. השאירה אחריה מחברת מתכונים אגדית.',
        ]);

        // דור 1 — הסבא והסבתא המייסדים
        $israel = $this->person($uid, 'ישראל', 'ישראלי', 'male', '1948-05-14', 'ה׳ באייר תש"ח', [
            'is_main_person' => true, 'city' => 'ירושלים',
            'current_occupation' => 'גמלאי, מורה להיסטוריה לשעבר',
            'bio' => 'נולד ביום הכרזת המדינה — ומכאן שמו. 35 שנה לימד היסטוריה, והיום מבלה בעיקר בגידול עגבניות במרפסת ובפינוק הנכדים.',
        ]);
        $rivka = $this->person($uid, 'רבקה', 'ישראלי', 'female', '1950-09-02', 'כ׳ באלול תש"י', [
            'maiden_name' => 'זהבי', 'city' => 'ירושלים',
            'current_occupation' => 'אופה הבית הרשמית',
            'bio' => 'אחות במקצועה ואופה בנשמתה. עוגת השוקולד שלה היא נכס משפחתי מוגן. אוספת עציצי סוקולנטים.',
        ]);

        // דור 2 — הילדים ובני/בנות הזוג
        $david = $this->person($uid, 'דוד', 'ישראלי', 'male', '1972-04-10', 'כ"ו בניסן תשל"ב', [
            'city' => 'רעננה', 'current_occupation' => 'אדריכל',
            'bio' => 'הבכור. מתכנן בתים לאחרים אבל השיפוץ בביתו שלו נמשך כבר עשור. חובב טיולי אופניים.',
        ]);
        $michal = $this->person($uid, 'מיכל', 'ישראלי', 'female', '1974-12-05', 'כ"א בכסלו תשל"ה', [
            'maiden_name' => 'פרחי', 'city' => 'רעננה', 'current_occupation' => 'רופאת ילדים',
            'bio' => 'רופאת ילדים שמצחיקה את המטופלים הקטנים עם בובות אצבע. אלופת המשפחה בפתרון תשבצים.',
        ]);
        $yael = $this->person($uid, 'יעל', 'אביב', 'female', '1975-08-21', 'י"ד באלול תשל"ה', [
            'maiden_name' => 'ישראלי', 'city' => 'תל אביב', 'current_occupation' => 'מורה למוזיקה',
            'bio' => 'מנצחת על מקהלת הילדים העירונית. בכל מפגש משפחתי היא זו שמארגנת את השירה בציבור.',
        ]);
        $uri = $this->person($uid, 'אורי', 'אביב', 'male', '1973-02-11', 'ט׳ באדר א׳ תשל"ג', [
            'city' => 'תל אביב', 'current_occupation' => 'מהנדס תוכנה',
            'bio' => 'כותב קוד ביום ומרכיב פאזלים של 2,000 חלקים בלילה. אחראי הברביקיו הבלתי מעורער של המשפחה.',
        ]);
        $yonatan = $this->person($uid, 'יונתן', 'ישראלי', 'male', '1978-11-30', 'ל׳ בחשוון תשל"ט', [
            'city' => 'חיפה', 'current_occupation' => 'שף ובעל מסעדה',
            'bio' => 'למד בישול בפריז וחזר לפתוח מסעדה קטנה בחיפה. טוען שהשקשוקה שלו תנצח כל שף צרפתי.',
        ]);
        $shira = $this->person($uid, 'שירה', 'ישראלי', 'female', '1980-05-17', 'ב׳ בסיוון תש"מ', [
            'maiden_name' => 'זמיר', 'city' => 'חיפה', 'current_occupation' => 'עורכת דין',
            'bio' => 'עורכת דין לזכויות עובדים. בזמנה הפנוי רצה מרתונים — הנכדים בטוחים שהיא הכי מהירה בעולם.',
        ]);
        $adi = $this->person($uid, 'עדי', 'גל', 'female', '1982-01-25', 'א׳ בשבט תשמ"ב', [
            'maiden_name' => 'ישראלי', 'city' => 'מודיעין', 'current_occupation' => 'מעצבת גרפית',
            'bio' => 'הצעירה של ישראל ורבקה. מעצבת את כל ההזמנות המשפחתיות, כולל ההזמנה לבת המצווה הקרובה.',
        ]);
        $noam = $this->person($uid, 'נועם', 'גל', 'male', '1981-07-07', 'ה׳ בתמוז תשמ"א', [
            'city' => 'מודיעין', 'current_occupation' => 'פיזיותרפיסט',
            'bio' => 'פיזיותרפיסט של קבוצת כדורסל. מלמד את כל הנכדים לקלוע לסל, בהצלחה חלקית.',
        ]);

        // דור 3 — הנכדים
        $noa = $this->person($uid, 'נועה', 'ישראלי', 'female', '2000-06-13', 'י׳ בסיוון תש"ס', [
            'city' => 'תל אביב', 'current_occupation' => 'סטודנטית לרפואה',
            'bio' => 'הנכדה הבכורה. בעקבות אמא — לומדת רפואה. התארסה לאחרונה לעומר, והחתונה כבר בפתח!',
        ]);
        $itamar = $this->person($uid, 'איתמר', 'ישראלי', 'male', '2003-10-04', 'ח׳ בתשרי תשס"ד', [
            'city' => 'רעננה', 'current_occupation' => 'מדריך טיולים צעיר',
            'bio' => 'אוהב את הטבע יותר מכל מסך. שביל ישראל? עשה. פעמיים.',
        ]);
        $tamarYoung = $this->person($uid, 'תמר', 'ישראלי', 'female', '2010-03-08', 'כ"ב באדר תש"ע', [
            'city' => 'רעננה', 'bio' => 'קרויה על שם סבתא-רבתא תמר. מציירת קומיקס על חיי המשפחה — כולם מחכים לפרק הבא.',
        ]);
        $amit = $this->person($uid, 'עמית', 'אביב', 'male', '2002-02-20', 'ח׳ באדר תשס"ב', [
            'city' => 'תל אביב', 'current_occupation' => 'סטודנט להנדסה',
            'bio' => 'ירש מאבא את אהבת הפאזלים ומאמא את הקול. סולן להקת הסטודנטים.',
        ]);
        $hillel = $this->person($uid, 'הלל', 'אביב', 'male', '2005-09-15', 'י"א באלול תשס"ה', [
            'city' => 'תל אביב', 'bio' => 'שחקן שחמט מושבע. ניצח את סבא ישראל בפעם הראשונה בגיל 11 — סבא עדיין טוען שנתן לו.',
        ]);
        $avigail = $this->person($uid, 'אביגיל', 'אביב', 'female', '2014-08-10', 'י"ד באב תשע"ד', [
            'city' => 'תל אביב', 'bio' => 'חוגגת בת מצווה בקרוב! רוקדת היפ-הופ ומגדלת שני אוגרים בשם גומי ונמש.',
        ]);
        $lavi = $this->person($uid, 'לביא', 'ישראלי', 'male', '2008-07-22', 'י"ט בתמוז תשס"ח', [
            'city' => 'חיפה', 'bio' => 'טבח צעיר במטבח של אבא. ההמבורגר שלו כבר נכנס לתפריט המסעדה.',
        ]);
        $carmel = $this->person($uid, 'כרמל', 'ישראלי', 'female', '2011-04-18', 'י"ד בניסן תשע"א', [
            'city' => 'חיפה', 'bio' => 'נולדה בערב פסח וקרויה על שם ההר שמול הבית. שוחה בנבחרת הצעירה של העיר.',
        ]);
        $boaz = $this->person($uid, 'בועז', 'ישראלי', 'male', '2015-12-01', 'י"ט בכסלו תשע"ו', [
            'city' => 'חיפה', 'bio' => 'הליצן של המשפחה. יודע לספר בדיחות אבצ״ד עוד לפני שלמד לקרוא.',
        ]);
        $ella = $this->person($uid, 'אלה', 'גל', 'female', '2013-05-30', 'כ"א בסיוון תשע"ג', [
            'city' => 'מודיעין', 'bio' => 'אספנית גלעיני משחקי קופסה. מנצחת את כולם ב"רביעיות משפחה" — כנראה יתרון ביתי.',
        ]);
        $yahli = $this->person($uid, 'יהלי', 'גל', 'male', '2016-10-12', 'י׳ בתשרי תשע"ז', [
            'city' => 'מודיעין', 'bio' => 'נולד ביום כיפור — הגיע לעולם בצום אבל מאז לא מפסיק לאכול. חובב דינוזאורים מושבע.',
        ]);
        $roni = $this->person($uid, 'רוני', 'גל', 'female', '2019-06-24', 'כ"א בסיוון תשע"ט', [
            'city' => 'מודיעין', 'bio' => 'הצעירה בשבט. כבר עכשיו ברור שהיא תנהל את כולם.',
        ]);

        // קישור המשתמשים לדמויות בעץ
        $admin->update(['person_id' => $israel->id]);
        $guest->update(['person_id' => $noa->id]);

        // ── קשרים ─────────────────────────────────────────────────
        // בני זוג (שורה אחת, person1 = id הנמוך)
        $this->spouse($avraham, $tamarSavta);
        $this->spouse($israel, $rivka, '1970-06-16', 'י"ב בסיוון תש"ל');
        $this->spouse($david, $michal, '1996-03-19', 'כ"ח באדר תשנ"ו');
        $this->spouse($yael, $uri, '1999-09-01', 'כ׳ באלול תשנ"ט');
        $this->spouse($yonatan, $shira, '2005-06-08', 'א׳ בסיוון תשס"ה');
        $this->spouse($adi, $noam, '2008-02-14', 'ח׳ באדר א׳ תשס"ח');

        // הורה → ילד (person1 = הורה)
        $this->children([$avraham, $tamarSavta], [$israel]);
        $this->children([$israel, $rivka], [$david, $yael, $yonatan, $adi]);
        $this->children([$david, $michal], [$noa, $itamar, $tamarYoung]);
        $this->children([$yael, $uri], [$amit, $hillel, $avigail]);
        $this->children([$yonatan, $shira], [$lavi, $carmel, $boaz]);
        $this->children([$adi, $noam], [$ella, $yahli, $roni]);

        // ── מיקומי ערים למפה ──────────────────────────────────────
        foreach ([
            ['ירושלים', 31.7683, 35.2137],
            ['רעננה',   32.1848, 34.8713],
            ['תל אביב', 32.0853, 34.7818],
            ['חיפה',    32.7940, 34.9896],
            ['מודיעין', 31.8928, 35.0153],
        ] as [$name, $lat, $lng]) {
            CityLocation::create(['name' => $name, 'lat' => $lat, 'lng' => $lng, 'created_by' => $uid]);
        }

        // ── אירועים ───────────────────────────────────────────────
        $batMitzvah = Event::create([
            'person_id'  => $avigail->id, 'type' => 'bat_mitzvah',
            'title'      => 'בת המצווה של אביגיל 🎉',
            'description'=> 'חוגגים לאביגיל שלנו בת מצווה! קבלת פנים בשש וחצי, ריקודים עד שהרגליים נופלות. מתנות לא חובה, חיבוקים כן.',
            'event_date' => Carbon::now()->addDays(20)->toDateString(),
            'event_time' => '18:30',
            'location'   => 'אולמי "הפרדס", רעננה (מקום בדוי)',
            'created_by' => $uid,
        ]);
        $wedding = Event::create([
            'person_id'  => $noa->id, 'type' => 'wedding',
            'title'      => 'החתונה של נועה ועומר 💍',
            'description'=> 'אחרי האירוסין המרגשים — נועה ועומר מתחתנים! חופה בשקיעה, מסיבה עד הזריחה.',
            'event_date' => Carbon::now()->addDays(52)->toDateString(),
            'event_time' => '19:00',
            'location'   => 'גן אירועים "בין ההדרים" (מקום בדוי)',
            'created_by' => $uid,
        ]);
        Event::create([
            'person_id'  => $israel->id, 'type' => 'other',
            'title'      => 'המפגש המשפחתי השנתי על הדשא',
            'description'=> 'הפיקניק המסורתי של משפחת ישראלי: אורי על הברביקיו, יעל על הגיטרה, וכולם על הדשא. להביא כובע ומצב רוח.',
            'event_date' => Carbon::now()->addDays(33)->toDateString(),
            'event_time' => '10:00',
            'location'   => 'פארק הירקון, תל אביב',
            'created_by' => $uid,
        ]);
        Event::create([
            'person_id'  => $avraham->id, 'type' => 'death',
            'title'      => 'אזכרה לסבא אברהם ז"ל',
            'description'=> 'עלייה לקבר ולאחריה מפגש בבית של סבא ישראל וסבתא רבקה. מי שזוכר סיפור על סבא אברהם — מוזמן לשתף.',
            'event_date' => Carbon::now()->addDays(9)->toDateString(),
            'event_time' => '16:00',
            'location'   => 'ירושלים',
            'created_by' => $uid,
        ]);
        Event::create([
            'person_id'  => $lavi->id, 'type' => 'other',
            'title'      => 'ערב טעימות במסעדה של יונתן',
            'description'=> 'יונתן מנסה תפריט חדש והמשפחה היא ועדת הטעימות. לביא אחראי על ההמבורגרים. ביקורות בונות בלבד!',
            'event_date' => Carbon::now()->subDays(14)->toDateString(),
            'event_time' => '19:30',
            'location'   => 'המסעדה של יונתן, חיפה (מקום בדוי)',
            'created_by' => $uid,
        ]);

        // ברכות, ריאקציות ומזל-טובים על האירועים
        Blessing::create(['event_id' => $batMitzvah->id, 'user_id' => $admin->id, 'message' => 'אביגיל היקרה, סבא וסבתא גאים בך עד השמיים! שתמשיכי לרקוד דרך החיים 💃']);
        Blessing::create(['event_id' => $batMitzvah->id, 'user_id' => $guest->id, 'message' => 'מזל טוב אחותי הקטנה והאהובה! את הכי מגניבה שיש 🎈']);
        Blessing::create(['event_id' => $wedding->id, 'user_id' => $admin->id, 'message' => 'נועה ועומר, שתבנו בית מלא שמחה, צחוק ועוגת שוקולד של סבתא רבקה ❤️']);
        EventReaction::create(['event_id' => $batMitzvah->id, 'user_id' => $admin->id, 'emoji' => '🎉']);
        EventReaction::create(['event_id' => $wedding->id, 'user_id' => $guest->id, 'emoji' => '❤️']);
        MazalTov::create(['event_id' => $batMitzvah->id, 'user_id' => $guest->id]);
        MazalTov::create(['event_id' => $wedding->id, 'user_id' => $admin->id]);

        // הודעות על פרופילים ("השאר הודעה")
        Message::create(['person_id' => $israel->id, 'user_id' => $guest->id, 'body' => 'סבא, העגבניות שלך מהמרפסת היו מושלמות בסלט של שבת. שומרים לך זרעים!']);
        Message::create(['person_id' => $rivka->id, 'user_id' => $guest->id, 'body' => 'סבתא, מחכים כבר לעוגה של יום שישי 🍫']);

        // ── סיפורי שמות ───────────────────────────────────────────
        NameStory::create([
            'person_id' => $israel->id, 'created_by' => $uid,
            'content'   => 'נולדתי ב-14 במאי 1948 — בדיוק ביום שבו דוד בן-גוריון הכריז על הקמת המדינה. אבא אברהם שמע את ההכרזה ברדיו בבית החולים, ובאותו רגע הוחלט: הבן הזה ייקרא ישראל. כל יום עצמאות הוא גם יום ההולדת שלי, ואני עדיין לא בטוח מי חוגג למי.',
        ]);
        NameStory::create([
            'person_id' => $tamarYoung->id, 'created_by' => $uid, 'named_after_person_id' => $tamarSavta->id,
            'content'   => 'תמר קרויה על שם סבתא-רבתא תמר ז"ל, התופרת האגדית של המשפחה. היא נולדה שנה אחרי פטירתה, וסבתא רבקה אומרת שכשתמר הקטנה מציירת — היא רואה בדיוק את אותו ריכוז שהיה לאמא שלה מול מכונת התפירה.',
        ]);
        NameStory::create([
            'person_id' => $carmel->id, 'created_by' => $uid,
            'content'   => 'כשהגענו לבית החולים בחיפה ללידה, מהחלון של חדר הלידה נשקף הר הכרמל בזריחה. שירה הסתכלה על ההר, הסתכלה על התינוקת, ואמרה: "זהו, אין מה להתלבט". וכך קיבלנו את כרמל שלנו.',
        ]);
        NameStory::create([
            'person_id' => $lavi->id, 'created_by' => $uid,
            'content'   => 'בהריון קראנו לו "גור" כי לא ידענו את המין. כשנולד עם רעמת שיער מרשימה במיוחד, האחות בחדר הלידה צחקה שהוא נראה כמו גור אריות — והשם לביא נתפס בו במקום.',
        ]);

        // ── מתכונים ───────────────────────────────────────────────
        $cake = Recipe::create([
            'title' => 'עוגת השוקולד המפורסמת של סבתא רבקה', 'person_id' => $rivka->id,
            'created_by' => $admin->id, 'category' => 'עוגות, שבת', 'quantity' => 'תבנית עגולה 26, כ-12 פרוסות',
            'is_favorite' => true,
            'ingredients' => "3 ביצים\nכוס סוכר\nכוס קמח תופח\nחצי כוס קקאו איכותי\nחצי כוס שמן\nכוס מים רותחים\nכפית תמצית וניל\nקורט מלח (הסוד של סבתא)",
            'preparation' => "מחממים תנור ל-170 מעלות.\nטורפים ביצים עם סוכר עד הבהרה.\nמוסיפים שמן ווניל וטורפים.\nמנפים פנימה קמח וקקאו, מוסיפים את המלח.\nמוזגים את המים הרותחים בהדרגה — הבלילה תהיה נוזלית, זה בסדר גמור!\nאופים 35 דקות. לא לפתוח את התנור לפני הזמן, סבתא רואה הכל.",
        ]);
        $hamin = Recipe::create([
            'title' => 'החמין הירושלמי של סבא ישראל', 'person_id' => $israel->id,
            'created_by' => $admin->id, 'category' => 'עיקריות, בשרי, שבת, מושקע', 'quantity' => 'סיר ענק, 10 סועדים',
            'ingredients' => "כוס וחצי שעועית לבנה (מושרית לילה)\nכוס גריסים\n6 תפוחי אדמה\nק\"ג בשר אסאדו עם עצם\n8 ביצים בקליפתן\nראש שום שלם\n2 בצלים מטוגנים היטב\nכף פפריקה מתוקה, כף דבש\nמלח, פלפל, ומים עד כיסוי",
            'preparation' => "מטגנים בצל בתחתית סיר כבד עד השחמה עמוקה.\nמסדרים שכבות: שעועית, גריסים, בשר, תפוחי אדמה, ביצים ושום.\nמתבלים, מוסיפים דבש ומים עד כיסוי.\nמרתיחים ומעבירים לפלטה מערב שבת.\nבבוקר הבית יתמלא בריח — זו לא תקלה, זו התוכנית.",
        ]);
        $shakshuka = Recipe::create([
            'title' => 'שקשוקה של שף (יונתן)', 'person_id' => $yonatan->id,
            'created_by' => $guest->id, 'category' => 'עיקריות, חלבי, פודי', 'quantity' => 'מחבת אחת, 2-3 רעבים',
            'ingredients' => "6 עגבניות בשלות מאוד\nפלפל אדום קלוי\n4 שיני שום\n4 ביצים\nכפית פפריקה חריפה\nחצי כפית כמון\nגבינת פטה לפירורים\nשמן זית, מלח, כוסברה למי שבצד הנכון של המפה",
            'preparation' => "מזהיבים שום בשמן זית.\nמוסיפים פלפל קלוי חתוך, ואז עגבניות מגוררות.\nמבשלים 20 דקות עד רוטב סמיך — סבלנות, זה כל הסוד.\nיוצרים גומות ושוברים פנימה ביצים.\nמכסים 4 דקות בדיוק, מפזרים פטה וכוסברה.\nמגישים עם לחם טרי בלבד. פיתה קנויה = עלבון למחבת.",
        ]);
        Recipe::create([
            'title' => 'עוגיות התמרים של מיכל', 'person_id' => $michal->id,
            'created_by' => $admin->id, 'category' => 'עוגיות', 'quantity' => 'כ-40 עוגיות (נגמרות ב-10 דקות)',
            'ingredients' => "500 גרם קמח\n200 גרם חמאה רכה\nחצי כוס סוכר\nכוס שמנת חמוצה\n500 גרם ממרח תמרים\nקינמון, אגוזי מלך קצוצים\nאבקת סוכר לקישוט",
            'preparation' => "מעבדים קמח, חמאה, סוכר ושמנת לבצק רך. מצננים שעה.\nמרדדים למלבן, מורחים ממרח תמרים ומפזרים אגוזים וקינמון.\nמגלגלים לרולדה וחותכים לפרוסות.\nאופים 20 דקות ב-180 מעלות.\nמפזרים אבקת סוכר ומתחבאים לפני שהילדים מגלים.",
        ]);
        Recipe::create([
            'title' => 'לימונדה של הנכדים', 'owner_text' => 'כל הנכדים ביחד',
            'created_by' => $guest->id, 'category' => 'שתייה, כללי', 'quantity' => 'קנקן גדול',
            'is_gluten_free' => true,
            'ingredients' => "8 לימונים סחוטים\nחצי כוס סוכר (או יותר, לא מספרים לאמא)\nליטר מים קרים\nנענע מהגינה של סבא\nהמון קרח",
            'preparation' => "סוחטים לימונים — זו העבודה של הגדולים.\nממיסים סוכר במעט מים חמים.\nמערבבים הכל בקנקן, טועמים, מוסיפים סוכר, טועמים שוב (זה החלק הכי חשוב).\nמגישים על הדשא במפגש המשפחתי.",
        ]);
        Recipe::create([
            'title' => 'סלט קינואה חגיגי של יעל', 'person_id' => $yael->id,
            'created_by' => $admin->id, 'category' => 'סלטים, ירקות', 'quantity' => 'קערה גדולה',
            'is_gluten_free' => true,
            'ingredients' => "כוס קינואה מבושלת ומצוננת\nמלפפון, פלפל אדום וצהוב\nחופן חמוציות\nחופן שקדים קלויים\nעשבי תיבול קצוצים בנדיבות\nלימון, שמן זית, מלח גס",
            'preparation' => "קוצצים את כל הירקות לקוביות קטנות ואחידות — יעל בודקת.\nמערבבים עם הקינואה, החמוציות והשקדים.\nמתבלים ממש לפני ההגשה כדי שיישאר פריך.\nמי שאומר \"עוד סלט קינואה?\" — לא מקבל.",
        ]);

        RecipeComment::create(['recipe_id' => $cake->id, 'user_id' => $guest->id, 'content' => 'הכנתי לשבת וזה נעלם לפני שהספקתי לצלם. סבתא, את אגדה.']);
        $c = RecipeComment::create(['recipe_id' => $shakshuka->id, 'user_id' => $admin->id, 'content' => 'יונתן, סבא ניסה והוסיף כמון כפול. יצא... מעניין.']);
        RecipeComment::create(['recipe_id' => $shakshuka->id, 'user_id' => $guest->id, 'parent_id' => $c->id, 'content' => 'סבא, "מעניין" זו לא ביקורת קולינרית 😂']);
        RecipeAdaptation::create(['recipe_id' => $cake->id, 'user_id' => $guest->id, 'content' => 'גרסה שלי: מחליפה חצי מהסוכר בסוכר קוקוס ומוסיפה שברי שוקולד מריר למעלה. סבתא עוד לא אישרה רשמית.']);

        // ── תמונות אלבום (פלייסהולדרים מצוירים) ────────────────────
        $photo1 = FamilyPhoto::create([
            'path' => $this->familyPhotoSvg(1, 'המפגש המשפחתי על הדשא', '#bae6fd', '#0369a1', '🌳🧺☀️'),
            'title' => 'המפגש המשפחתי השנתי — תמונת הדגמה מצוירת', 'uploaded_by' => $admin->id,
        ]);
        $photo2 = FamilyPhoto::create([
            'path' => $this->familyPhotoSvg(2, 'ערב טעימות אצל יונתן', '#fde68a', '#b45309', '🍳👨‍🍳🍋'),
            'title' => 'ערב הטעימות במסעדה — תמונת הדגמה מצוירת', 'uploaded_by' => $guest->id,
        ]);
        FamilyPhoto::create([
            'path' => $this->familyPhotoSvg(3, 'חנוכה אצל סבא וסבתא', '#ddd6fe', '#5b21b6', '🕎🍩✨'),
            'title' => 'הדלקת נרות משפחתית — תמונת הדגמה מצוירת', 'uploaded_by' => $admin->id,
        ]);

        // תיוגים על התמונות
        foreach ([[$israel, 18, 40], [$rivka, 38, 42], [$avigail, 62, 55], [$boaz, 80, 58]] as [$p, $x, $y]) {
            PhotoTag::create(['family_photo_id' => $photo1->id, 'person_id' => $p->id, 'x_percent' => $x, 'y_percent' => $y, 'w_percent' => 12, 'h_percent' => 16]);
        }
        foreach ([[$yonatan, 30, 45], [$lavi, 55, 50]] as [$p, $x, $y]) {
            PhotoTag::create(['family_photo_id' => $photo2->id, 'person_id' => $p->id, 'x_percent' => $x, 'y_percent' => $y, 'w_percent' => 12, 'h_percent' => 16]);
        }

        // ── נתוני משחק ─────────────────────────────────────────────
        foreach ([[$israel, 14, 340], [$rivka, 12, 300], [$boaz, 9, 210], [$avigail, 7, 160], [$lavi, 4, 90]] as [$p, $g, $pts]) {
            GameStat::create(['person_id' => $p->id, 'correct_guesses' => $g, 'points' => $pts]);
        }
    }

    /** יצירת אדם עם אווטאר SVG של ראשי תיבות */
    private function person(int $createdBy, string $first, string $last, string $gender, string $birthG, string $birthH, array $extra = []): Person
    {
        return Person::create(array_merge([
            'first_name'           => $first,
            'last_name'            => $last,
            'gender'               => $gender,
            'birth_date_gregorian' => $birthG,
            'birth_date_hebrew'    => $birthH,
            'profile_photo'        => $this->avatarSvg(mb_substr($first, 0, 1)),
            'created_by'           => $createdBy,
        ], $extra));
    }

    /** בן/בת זוג — שורה אחת, person1 = ה-id הנמוך (כמו ב-PersonController) */
    private function spouse(Person $a, Person $b, ?string $marriageG = null, ?string $marriageH = null): void
    {
        Relationship::create([
            'person1_id'              => min($a->id, $b->id),
            'person2_id'              => max($a->id, $b->id),
            'type'                    => 'spouse',
            'marriage_date_gregorian' => $marriageG,
            'marriage_date_hebrew'    => $marriageH,
        ]);
    }

    /** קשרי הורה→ילד לכל צירוף הורה/ילד, עם sort_order לפי סדר הלידה */
    private function children(array $parents, array $kids): void
    {
        foreach ($parents as $parent) {
            foreach ($kids as $i => $kid) {
                Relationship::create([
                    'person1_id' => $parent->id,
                    'person2_id' => $kid->id,
                    'type'       => 'parent_child',
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }

    /** אווטאר SVG פשוט — עיגול צבעוני עם האות הראשונה של השם */
    private function avatarSvg(string $letter): string
    {
        $palettes = [
            ['#fde68a', '#92400e'], ['#bfdbfe', '#1e40af'], ['#bbf7d0', '#166534'],
            ['#fbcfe8', '#9d174d'], ['#ddd6fe', '#5b21b6'], ['#fed7aa', '#9a3412'],
            ['#a5f3fc', '#155e75'], ['#e9d5ff', '#6b21a8'], ['#d9f99d', '#3f6212'],
        ];
        [$bg, $fg] = $palettes[$this->avatarIndex % count($palettes)];
        $i = ++$this->avatarIndex;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">'
            . '<rect width="300" height="300" rx="24" fill="' . $bg . '"/>'
            . '<circle cx="150" cy="150" r="105" fill="#ffffff" opacity="0.45"/>'
            . '<text x="150" y="150" font-family="Arial, sans-serif" font-size="120" font-weight="bold" fill="' . $fg . '" text-anchor="middle" dominant-baseline="central">' . $letter . '</text>'
            . '</svg>';

        $path = "avatars/demo/avatar-{$i}.svg";
        Storage::disk('public')->put($path, $svg);
        return $path;
    }

    /** "תמונה משפחתית" מצוירת — פלייסהולדר SVG ברור-שאינו-אמיתי לאלבום */
    private function familyPhotoSvg(int $i, string $caption, string $bg, string $fg, string $emoji): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">'
            . '<rect width="1200" height="800" fill="' . $bg . '"/>'
            . '<circle cx="600" cy="330" r="210" fill="#ffffff" opacity="0.5"/>'
            . '<text x="600" y="330" font-size="150" text-anchor="middle" dominant-baseline="central">' . $emoji . '</text>'
            . '<text x="600" y="600" font-family="Arial, sans-serif" font-size="52" font-weight="bold" fill="' . $fg . '" text-anchor="middle">' . $caption . '</text>'
            . '<text x="600" y="670" font-family="Arial, sans-serif" font-size="30" fill="' . $fg . '" opacity="0.8" text-anchor="middle">🎭 איור להדגמה — לא תמונה אמיתית</text>'
            . '</svg>';

        $path = "family-photos/demo/photo-{$i}.svg";
        Storage::disk('public')->put($path, $svg);
        return $path;
    }
}
