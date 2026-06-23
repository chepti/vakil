<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityLocation extends Model
{
    protected $fillable = ['name', 'lat', 'lng', 'created_by'];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];
}
