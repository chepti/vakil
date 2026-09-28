<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * מייבא את מתכוני סבתא אסתר ואקיל מתוך ספר הבישול "מבשלים עם סבתא – מתכוני קיץ".
 * הנתונים והתמונות יושבים ב-database/seeders/esther_summer. הרצה חוזרת מעדכנת ולא משכפלת
 * (זיהוי לפי כותרת + מקור).
 */
class ImportEstherSummerRecipes extends Command
{
    protected $signature = 'recipes:import-esther-summer {--dry-run : רק להציג מה ייובא}';

    protected $description = 'ייבוא מתכוני סבתא אסתר ואקיל מספר הבישול "מבשלים עם סבתא – מתכוני קיץ"';

    public const SOURCE = 'מתוך ספר הבישול "מבשלים עם סבתא – מתכוני קיץ"';

    public function handle(): int
    {
        $dir = database_path('seeders/esther_summer');
        $recipes = json_decode(file_get_contents($dir . '/recipes.json'), true);
        if (!is_array($recipes)) {
            $this->error('recipes.json parse failed: ' . json_last_error_msg());
            return self::FAILURE;
        }

        $person = Person::where('first_name', 'אסתר')->where('last_name', 'ואקיל')->first();
        if (!$person) {
            $this->error('לא נמצאה דמות "אסתר ואקיל"');
            return self::FAILURE;
        }

        $adminId = User::where('role', 'admin')->value('id') ?? User::first()->id;

        foreach ($recipes as $r) {
            $existing = Recipe::where('title', $r['title'])->where('source', self::SOURCE)->first();
            $this->line(($existing ? 'עדכון' : 'חדש') . ': ' . $r['title']);
            if ($this->option('dry-run')) {
                continue;
            }

            $imagePath = 'recipes/esther-summer-' . $r['slug'] . '.jpg';
            Storage::disk('public')->put($imagePath, file_get_contents($dir . '/' . $r['slug'] . '.jpg'));

            $data = [
                'title'          => $r['title'],
                'category'       => $r['category'],
                'quantity'       => $r['quantity'] ?? null,
                'ingredients'    => implode("\n", $r['ingredients']),
                'preparation'    => implode("\n", $r['preparation']),
                'notes'          => $r['notes'] ? implode("\n", $r['notes']) : null,
                'source'         => self::SOURCE,
                'owner_text'     => 'סבתא אסתר ואקיל',
                'person_id'      => $person->id,
                'is_favorite'    => false,
                'is_gluten_free' => $r['is_gluten_free'],
                'image'          => $imagePath,
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                Recipe::create($data + ['created_by' => $adminId]);
            }
        }

        $this->info('סה"כ ' . count($recipes) . ' מתכונים, משויכים ל' . $person->full_name . ' (#' . $person->id . ')');
        return self::SUCCESS;
    }
}
