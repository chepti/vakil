<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Relationship;
use App\Services\Family\DateVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * התאמות תצוגת התאריכים פר-אתר — כבויות כברירת מחדל (ואקיל/הדגמה),
 * דלוקות רק היכן שה-.env מפעיל אותן (בן ארצי).
 */
class DateVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function person(string $name, string $gender): Person
    {
        return Person::create(['first_name' => $name, 'last_name' => 'בן ארצי', 'gender' => $gender]);
    }

    public function test_flags_are_off_by_default(): void
    {
        $this->assertFalse(DateVisibility::hideGregorian());
        $this->assertSame([], DateVisibility::hiddenBirthYearIds());
    }

    public function test_only_married_women_have_their_birth_year_hidden(): void
    {
        config(['app.dates.hide_married_women_birth_year' => true]);

        $husband = $this->person('בעל', 'male');
        $wife    = $this->person('אישה נשואה', 'female');
        $single  = $this->person('רווקה', 'female');

        Relationship::create([
            'person1_id' => $husband->id, 'person2_id' => $wife->id, 'type' => 'spouse',
        ]);

        $hidden = DateVisibility::hiddenBirthYearIds();

        $this->assertContains($wife->id, $hidden);
        $this->assertNotContains($single->id, $hidden);
        $this->assertNotContains($husband->id, $hidden, 'הכלל חל על נשים בלבד');

        $this->assertTrue(DateVisibility::birthYearHidden($wife->id));
        $this->assertFalse(DateVisibility::birthYearHidden($single->id));
    }

    public function test_the_flag_gates_the_whole_computation(): void
    {
        $husband = $this->person('בעל', 'male');
        $wife    = $this->person('אישה נשואה', 'female');
        Relationship::create([
            'person1_id' => $husband->id, 'person2_id' => $wife->id, 'type' => 'spouse',
        ]);

        config(['app.dates.hide_married_women_birth_year' => false]);

        $this->assertSame([], DateVisibility::hiddenBirthYearIds());
        $this->assertFalse(DateVisibility::birthYearHidden($wife->id));
    }
}
