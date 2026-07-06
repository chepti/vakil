<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoDedupTest extends TestCase
{
    use RefreshDatabase;

    private Person $person;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.test',
            'password' => 'secret-secret', 'role' => 'admin', 'status' => 'active',
        ]);
        $this->person = Person::create([
            'first_name' => 'א', 'last_name' => 'ב', 'gender' => 'male', 'created_by' => $admin->id,
        ]);
    }

    public function test_dedup_merges_identical_originals_and_frees_space(): void
    {
        // אותו תוכן-מקור, שלושה קבצים שונים (כמו שלושה חיתוכים מאותה תמונה)
        $content = str_repeat('IMG-DATA-', 1000);
        foreach (['a', 'b', 'c'] as $k) {
            Storage::disk('public')->put("photos/originals/{$k}.jpg", $content);
        }
        // תוכן שונה — לא אמור להימחק
        Storage::disk('public')->put('photos/originals/other.jpg', 'DIFFERENT');

        $p1 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/1.jpg', 'original_path' => 'photos/originals/a.jpg']);
        $p2 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/2.jpg', 'original_path' => 'photos/originals/b.jpg']);
        $p3 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/3.jpg', 'original_path' => 'photos/originals/c.jpg']);
        $p4 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/4.jpg', 'original_path' => 'photos/originals/other.jpg']);

        $this->artisan('photos:dedup-originals')->assertSuccessful();

        // כל שלוש הרשומות מצביעות על אותו קובץ קנוני
        $canonical = $p1->fresh()->original_path;
        $this->assertSame($canonical, $p2->fresh()->original_path);
        $this->assertSame($canonical, $p3->fresh()->original_path);
        $this->assertSame('photos/originals/a.jpg', $canonical, 'הקנוני = הראשון לפי id');

        // הקבצים הכפולים נמחקו, הקנוני והשונה נשארו
        Storage::disk('public')->assertExists('photos/originals/a.jpg');
        Storage::disk('public')->assertMissing('photos/originals/b.jpg');
        Storage::disk('public')->assertMissing('photos/originals/c.jpg');
        Storage::disk('public')->assertExists('photos/originals/other.jpg');
        $this->assertSame('photos/originals/other.jpg', $p4->fresh()->original_path);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $content = str_repeat('X', 500);
        Storage::disk('public')->put('photos/originals/a.jpg', $content);
        Storage::disk('public')->put('photos/originals/b.jpg', $content);
        $p1 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/1.jpg', 'original_path' => 'photos/originals/a.jpg']);
        $p2 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/2.jpg', 'original_path' => 'photos/originals/b.jpg']);

        $this->artisan('photos:dedup-originals --dry')->assertSuccessful();

        $this->assertSame('photos/originals/b.jpg', $p2->fresh()->original_path, 'dry לא ממפה מחדש');
        Storage::disk('public')->assertExists('photos/originals/b.jpg');
    }

    public function test_shared_original_survives_until_last_reference_deleted(): void
    {
        Storage::disk('public')->put('photos/originals/shared.jpg', 'SRC');
        Storage::disk('public')->put('avatars/1.jpg', 'T1');
        Storage::disk('public')->put('avatars/2.jpg', 'T2');

        $p1 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/1.jpg', 'original_path' => 'photos/originals/shared.jpg']);
        $p2 = Photo::create(['person_id' => $this->person->id, 'thumb_path' => 'avatars/2.jpg', 'original_path' => 'photos/originals/shared.jpg']);

        // מחיקת התייחסות אחת — המקור שורד כי p2 עדיין מפנה אליו
        $p1->delete();
        PhotoStorage::deleteIfUnreferenced('avatars/1.jpg');
        PhotoStorage::deleteIfUnreferenced('photos/originals/shared.jpg');
        Storage::disk('public')->assertExists('photos/originals/shared.jpg');
        Storage::disk('public')->assertMissing('avatars/1.jpg');

        // מחיקת ההתייחסות האחרונה — עכשיו המקור נמחק
        $p2->delete();
        PhotoStorage::deleteIfUnreferenced('avatars/2.jpg');
        PhotoStorage::deleteIfUnreferenced('photos/originals/shared.jpg');
        Storage::disk('public')->assertMissing('photos/originals/shared.jpg');
    }
}
