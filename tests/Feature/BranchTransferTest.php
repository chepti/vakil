<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FamilyPhoto;
use App\Models\NameStory;
use App\Models\Person;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\Relationship;
use App\Models\User;
use App\Services\Branch\BranchExportService;
use App\Services\Branch\BranchImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BranchTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.test',
            'password' => 'secret-secret', 'role' => 'admin', 'status' => 'active',
        ]);
    }

    /**
     * בונה עץ קטן:
     * סבא (main) ─┬─ בן א' + אשתו ─── נכד
     *             └─ בת ב' (מחוץ לענף המיוצא)
     * הענף המיוצא: בן א'.
     */
    private function seedTree(): array
    {
        $grandpa = Person::create(['first_name' => 'סבא', 'last_name' => 'ואקיל', 'gender' => 'male', 'is_main_person' => true, 'created_by' => $this->admin->id]);
        $sonA    = Person::create(['first_name' => 'בן', 'last_name' => 'ואקיל', 'gender' => 'male', 'created_by' => $this->admin->id, 'profile_photo' => 'profiles/son-a.jpg']);
        $wifeA   = Person::create(['first_name' => 'כלה', 'last_name' => 'ואקיל', 'gender' => 'female', 'created_by' => $this->admin->id]);
        $grandkid = Person::create(['first_name' => 'נכד', 'last_name' => 'ואקיל', 'gender' => 'male', 'created_by' => $this->admin->id]);
        $daughterB = Person::create(['first_name' => 'בת', 'last_name' => 'ואקיל', 'gender' => 'female', 'created_by' => $this->admin->id]);

        Relationship::create(['person1_id' => $grandpa->id, 'person2_id' => $sonA->id, 'type' => 'parent_child']);
        Relationship::create(['person1_id' => $grandpa->id, 'person2_id' => $daughterB->id, 'type' => 'parent_child']);
        Relationship::create(['person1_id' => $sonA->id, 'person2_id' => $wifeA->id, 'type' => 'spouse']);
        Relationship::create(['person1_id' => $sonA->id, 'person2_id' => $grandkid->id, 'type' => 'parent_child']);
        Relationship::create(['person1_id' => $wifeA->id, 'person2_id' => $grandkid->id, 'type' => 'parent_child']);

        $event = Event::create(['person_id' => $grandkid->id, 'type' => 'birthday', 'title' => 'יום הולדת לנכד', 'event_date' => '2026-08-01', 'created_by' => $this->admin->id]);
        // סיפור שם שמצביע על דמות מחוץ לענף המיוצא (הסבא) — ה-FK חייב להתאפס בייבוא
        NameStory::create(['person_id' => $grandkid->id, 'created_by' => $this->admin->id, 'content' => 'נקרא על שם הסבא', 'named_after_person_id' => $grandpa->id]);
        // וסיפור שם שמצביע פנימה (על האבא) — ה-FK חייב להתמפות לזהות החדשה
        NameStory::create(['person_id' => $wifeA->id, 'created_by' => $this->admin->id, 'content' => 'על שם בעלה', 'named_after_person_id' => $sonA->id]);
        Photo::create(['person_id' => $grandkid->id, 'thumb_path' => 'photos/kid-thumb.jpg', 'uploaded_by' => $this->admin->id]);

        // תמונת אלבום עם תיוג בתוך הענף ומחוצה לו
        $familyPhoto = FamilyPhoto::create(['path' => 'family/reunion.jpg', 'title' => 'מפגש', 'uploaded_by' => $this->admin->id]);
        PhotoTag::create(['family_photo_id' => $familyPhoto->id, 'person_id' => $sonA->id, 'x_percent' => 10, 'y_percent' => 10, 'w_percent' => 20, 'h_percent' => 20]);
        PhotoTag::create(['family_photo_id' => $familyPhoto->id, 'person_id' => $daughterB->id, 'x_percent' => 50, 'y_percent' => 10, 'w_percent' => 20, 'h_percent' => 20]);

        // קבצי מדיה מזויפים
        Storage::disk('public')->put('profiles/son-a.jpg', 'fake-jpg-1');
        Storage::disk('public')->put('photos/kid-thumb.jpg', 'fake-jpg-2');
        Storage::disk('public')->put('family/reunion.jpg', 'fake-jpg-3');

        return compact('grandpa', 'sonA', 'wifeA', 'grandkid', 'daughterB', 'event');
    }

    public function test_every_record_gets_origin_uuid_automatically(): void
    {
        $t = $this->seedTree();
        $this->assertNotEmpty($t['sonA']->origin_uuid);
        $this->assertNotEmpty(Relationship::first()->origin_uuid);
        $this->assertNotEmpty($t['event']->origin_uuid);
    }

    public function test_branch_member_collection_stops_at_spouses(): void
    {
        $t = $this->seedTree();
        $ids = app(BranchExportService::class)->collectBranchMemberIds($t['sonA']);

        sort($ids);
        $expected = [$t['sonA']->id, $t['wifeA']->id, $t['grandkid']->id];
        sort($expected);

        $this->assertSame($expected, $ids, 'הענף: בן + אשתו + נכד — בלי סבא ובלי בת ב\'');
    }

    public function test_full_round_trip_export_wipe_import(): void
    {
        $t = $this->seedTree();
        $rootUuid = $t['sonA']->origin_uuid;

        $zipPath = app(BranchExportService::class)->export($t['sonA'], sys_get_temp_dir());

        // "אתר יעד טרי": מוחקים את כל הנתונים (המשתמש-אדמין נשאר)
        foreach (['photo_tags', 'photos', 'name_stories', 'events', 'relationships', 'family_photos', 'people'] as $table) {
            DB::table($table)->delete();
        }
        Storage::fake('public'); // גם המדיה "לא קיימת" ביעד

        $result = app(BranchImportService::class)->import($zipPath, $this->admin);

        // 3 דמויות בענף, כולן נוצרו
        $this->assertSame(3, Person::count());
        $this->assertSame(3, $result['stats']['people']['created']);

        // השורש הפך ל-main ביעד
        $root = Person::where('origin_uuid', $rootUuid)->firstOrFail();
        $this->assertTrue($root->is_main_person);
        $this->assertSame(1, Person::where('is_main_person', true)->count());

        // קשרים: רק 3 הפנימיים (זוגיות + שני הורה-ילד לנכד) — וה-FK מופו לזהויות החדשות
        $this->assertSame(3, Relationship::count());
        $grandkid = Person::where('first_name', 'נכד')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$root->id, Person::where('first_name', 'כלה')->value('id')],
            $grandkid->parents()->pluck('people.id')->all(),
        );

        // תוכן צמוד הגיע ומופה
        $this->assertSame(1, Event::count());
        $this->assertSame($grandkid->id, Event::first()->person_id);
        $this->assertSame('נקרא על שם הסבא', $grandkid->nameStory->content);

        // named_after_person_id: מחוץ לענף → null, בתוך הענף → מופה לזהות החדשה
        $this->assertNull($grandkid->nameStory->named_after_person_id, 'הסבא לא נסע בענף — הקישור אליו חייב להתאפס');
        $wife = Person::where('first_name', 'כלה')->firstOrFail();
        $this->assertSame($root->id, $wife->nameStory->named_after_person_id);
        $this->assertSame(1, Photo::where('person_id', $grandkid->id)->count());

        // אלבום: התמונה הגיעה, אבל רק התיוג של חבר-הענף (לא של בת ב')
        $this->assertSame(1, FamilyPhoto::count());
        $this->assertSame(1, PhotoTag::count());
        $this->assertSame($root->id, PhotoTag::first()->person_id);

        // creator מופה לאדמין המייבא
        $this->assertSame($this->admin->id, $root->created_by);
        $this->assertSame($this->admin->id, Event::first()->created_by);

        // המדיה חולצה לנתיבים המקוריים
        Storage::disk('public')->assertExists('profiles/son-a.jpg');
        Storage::disk('public')->assertExists('photos/kid-thumb.jpg');
        Storage::disk('public')->assertExists('family/reunion.jpg');

        @unlink($zipPath);
    }

    public function test_reimport_is_idempotent_updates_instead_of_duplicating(): void
    {
        $t = $this->seedTree();
        $zipPath = app(BranchExportService::class)->export($t['sonA'], sys_get_temp_dir());

        foreach (['photo_tags', 'photos', 'name_stories', 'events', 'relationships', 'family_photos', 'people'] as $table) {
            DB::table($table)->delete();
        }

        $importer = app(BranchImportService::class);
        $importer->import($zipPath, $this->admin);

        $second = app(BranchImportService::class)->import($zipPath, $this->admin);

        $this->assertSame(3, Person::count(), 'ייבוא חוזר לא משכפל דמויות');
        $this->assertSame(0, $second['stats']['people']['created']);
        $this->assertSame(3, $second['stats']['people']['updated']);
        $this->assertSame(3, Relationship::count());
        $this->assertSame(1, Event::count());

        @unlink($zipPath);
    }
}
