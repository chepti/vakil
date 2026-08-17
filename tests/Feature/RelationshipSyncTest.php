<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Relationship;
use App\Models\User;
use App\Services\Family\RelationshipSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * השלמת הורות אוטומטית סביב זוגיות — מוסיפים בן/בת זוג והם נהיים
 * הורים של הילדים הקיימים, ולהיפך.
 */
class RelationshipSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.test',
            'password' => 'secret-secret', 'role' => 'admin', 'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function person(string $name, string $gender = 'male'): Person
    {
        return Person::create([
            'first_name' => $name, 'last_name' => 'בן ארצי',
            'gender' => $gender, 'created_by' => $this->admin->id,
        ]);
    }

    private function marry(Person $a, Person $b): void
    {
        Relationship::create([
            'person1_id' => min($a->id, $b->id),
            'person2_id' => max($a->id, $b->id),
            'type'       => 'spouse',
        ]);
    }

    private function parentOf(Person $parent, Person $child, bool $explicit = false): void
    {
        Relationship::create([
            'person1_id' => $parent->id, 'person2_id' => $child->id,
            'type' => 'parent_child', 'is_explicit' => $explicit,
        ]);
    }

    public function test_adding_a_spouse_makes_them_parent_of_existing_children(): void
    {
        $father = $this->person('אב');
        $mother = $this->person('אם', 'female');
        $kid1   = $this->person('בן א');
        $kid2   = $this->person('בת ב', 'female');
        $this->parentOf($father, $kid1);
        $this->parentOf($father, $kid2);

        $this->actingAs($this->admin)
            ->post(route('people.spouse', $father), ['spouse_id' => $mother->id])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$father->id, $mother->id],
            $kid1->parents()->pluck('people.id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$father->id, $mother->id],
            $kid2->parents()->pluck('people.id')->all(),
        );
    }

    public function test_it_works_in_the_other_direction_too(): void
    {
        $father = $this->person('אב');
        $mother = $this->person('אם', 'female');
        $herKid = $this->person('ילד של האם');
        $this->parentOf($mother, $herKid);
        $this->marry($father, $mother);

        app(RelationshipSync::class)->completeParenthoodForCouple($father, $mother);

        $this->assertEqualsCanonicalizing(
            [$mother->id, $father->id],
            $herKid->parents()->pluck('people.id')->all(),
        );
    }

    public function test_second_marriage_is_left_alone(): void
    {
        $father  = $this->person('אב');
        $first   = $this->person('אשה ראשונה', 'female');
        $second  = $this->person('אשה שניה', 'female');
        $kid     = $this->person('ילד מהנישואים הראשונים');
        $this->parentOf($father, $kid);
        $this->marry($father, $first);
        $this->marry($father, $second);

        app(RelationshipSync::class)->completeParenthoodForCouple($father, $second);

        $this->assertSame(
            [$father->id],
            $kid->parents()->pluck('people.id')->all(),
            'עם שתי בנות זוג אי אפשר לדעת מאיזו זוגיות הילד — לא נוגעים',
        );
    }

    public function test_child_with_two_parents_or_explicit_assignment_is_not_touched(): void
    {
        $father   = $this->person('אב');
        $mother   = $this->person('אם', 'female');
        $stepMom  = $this->person('אם חורגת', 'female');
        $full     = $this->person('ילד עם שני הורים');
        $explicit = $this->person('ילד משויך ידנית');

        $this->parentOf($father, $full);
        $this->parentOf($mother, $full);
        $this->parentOf($father, $explicit, explicit: true);

        app(RelationshipSync::class)->completeParenthoodForCouple($father, $stepMom);

        $this->assertEqualsCanonicalizing([$father->id, $mother->id], $full->parents()->pluck('people.id')->all());
        $this->assertSame([$father->id], $explicit->parents()->pluck('people.id')->all());
    }

    public function test_adding_a_parent_completes_the_second_parent_from_their_spouse(): void
    {
        $father = $this->person('אב');
        $mother = $this->person('אם', 'female');
        $kid    = $this->person('ילד');
        $this->marry($father, $mother);

        $this->actingAs($this->admin)
            ->post(route('people.parent', $kid), ['existing_id' => $father->id])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$father->id, $mother->id],
            $kid->parents()->pluck('people.id')->all(),
        );
    }

    public function test_new_child_created_with_one_parent_gets_the_spouse_as_second_parent(): void
    {
        $father = $this->person('אב');
        $mother = $this->person('אם', 'female');
        $this->marry($father, $mother);

        $this->actingAs($this->admin)->post(route('people.store'), [
            'first_name' => 'תינוק', 'last_name' => 'בן ארצי', 'gender' => 'male',
            'parent_ids' => [$father->id],
        ])->assertRedirect();

        $kid = Person::where('first_name', 'תינוק')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$father->id, $mother->id],
            $kid->parents()->pluck('people.id')->all(),
        );
    }

    public function test_explicit_single_parent_stays_single(): void
    {
        $father = $this->person('אב');
        $mother = $this->person('אם', 'female');
        $this->marry($father, $mother);

        $this->actingAs($this->admin)->post(route('people.store'), [
            'first_name' => 'ילד מיוחס לאב בלבד', 'last_name' => 'בן ארצי', 'gender' => 'male',
            'parent_ids' => [$father->id], 'explicit_parents' => true,
        ])->assertRedirect();

        $kid = Person::where('first_name', 'ילד מיוחס לאב בלבד')->firstOrFail();
        $this->assertSame([$father->id], $kid->parents()->pluck('people.id')->all());
    }
}
