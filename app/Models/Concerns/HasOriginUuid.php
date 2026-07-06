<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * מוסיף origin_uuid — תעודת-זהות גלובלית יציבה חוצת-אתרים.
 * כל רשומה חדשה מקבלת UUID אוטומטית (בלי לדרוס ערך שכבר הובא בייבוא),
 * כדי שאותה ישות תישא אותו מזהה בשני האתרים ותאפשר סנכרון עתידי לפי UUID.
 */
trait HasOriginUuid
{
    protected static function bootHasOriginUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->origin_uuid)) {
                $model->origin_uuid = (string) Str::uuid();
            }
        });
    }
}
