<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipesSeeder extends Seeder
{
    public function run(): void
    {
        if (Recipe::count() > 0) { return; }

        $adminId = User::where('role', 'admin')->value('id') ?? User::first()->id;

        $findPerson = function (string $name): ?int {
            if (!$name) return null;
            $clean = preg_replace('/סבתא|סב|אמא|דודה|של\s+/u', '', $name);
            $clean = trim($clean);
            $parts = preg_split('/\s+/u', $clean);
            foreach ($parts as $p) {
                if (mb_strlen($p) < 2) continue;
                $found = Person::where('first_name', $p)->orWhere('last_name', $p)->value('id');
                if ($found) return $found;
            }
            return null;
        };

        $jsonPath = __DIR__ . '/recipes_data.json';
        if (!file_exists($jsonPath)) {
            $this->command->warn('recipes_data.json not found — skipping recipe import.');
            return;
        }

        $recipes = json_decode(file_get_contents($jsonPath), true);

        foreach ($recipes as $data) {
            $ownerText = $data['owner_text'] ?? '';
            $personId  = $findPerson($ownerText);

            Recipe::create([
                'title'          => $data['title'],
                'ingredients'    => $data['ingredients'],
                'preparation'    => $data['preparation'],
                'owner_text'     => $ownerText ?: null,
                'person_id'      => $personId,
                'quantity'       => $data['quantity'] ?: null,
                'category'       => $data['category'],
                'is_favorite'    => $data['is_favorite'],
                'is_gluten_free' => $data['is_gluten_free'],
                'created_by'     => $adminId,
            ]);
        }

        $this->command->info('Imported ' . count($recipes) . ' recipes.');
    }
}
