<?php

namespace App\Services\Branch;

use App\Models\Event;
use App\Models\FamilyPhoto;
use App\Models\NameStory;
use App\Models\Person;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\Relationship;

/**
 * תיאור-הסכמה המשותף לייצוא ולייבוא של "ענף".
 *
 * מקור אמת יחיד: איזה טבלאות נוסעות, מה ה-FK בכל אחת (ומאיזה ישות),
 * אילו שדות הם קבצי-מדיה, ואיזה שדה "נוצר ע"י" צריך למפות לאדמין המייבא.
 *
 * הסדר במערך הוא סדר הייבוא — כל ישות מיובאת רק אחרי שכל מה שהיא
 * מצביעה עליו (via fk) כבר יובא, כך שאפשר למפות origin_uuid ל-id מקומי.
 */
class BranchSchema
{
    public const SCHEMA_VERSION = 1;

    /**
     * @return array<string, array{
     *   model: class-string,
     *   fk: array<string, string>,   // localColumn => referenced entity key
     *   media: string[],             // columns holding storage-relative paths
     *   creator: ?string             // user-FK column to remap to the importing admin
     * }>
     */
    public static function tables(): array
    {
        return [
            'people' => [
                'model'   => Person::class,
                'fk'      => [],
                'media'   => ['profile_photo'],
                'creator' => 'created_by',
            ],
            'family_photos' => [
                'model'   => FamilyPhoto::class,
                'fk'      => [],
                'media'   => ['path'],
                'creator' => 'uploaded_by',
            ],
            'relationships' => [
                'model'   => Relationship::class,
                'fk'      => ['person1_id' => 'people', 'person2_id' => 'people'],
                'media'   => [],
                'creator' => null,
            ],
            'events' => [
                'model'   => Event::class,
                'fk'      => ['person_id' => 'people', 'audience_branch_person_id' => 'people'],
                'media'   => ['invitation_image'],
                'creator' => 'created_by',
            ],
            'name_stories' => [
                'model'   => NameStory::class,
                'fk'      => ['person_id' => 'people'],
                'media'   => [],
                'creator' => 'created_by',
            ],
            'photos' => [
                'model'   => Photo::class,
                'fk'      => ['person_id' => 'people', 'event_id' => 'events'],
                'media'   => ['thumb_path', 'original_path'],
                'creator' => 'uploaded_by',
            ],
            'photo_tags' => [
                'model'   => PhotoTag::class,
                'fk'      => ['family_photo_id' => 'family_photos', 'person_id' => 'people'],
                'media'   => [],
                'creator' => null,
            ],
        ];
    }

    /** עמודות שלא נכתבות ישירות בייבוא (id מנוהל מקומית, timestamps ע"י Eloquent). */
    public const SKIP_COLUMNS = ['id', 'created_at', 'updated_at'];
}
