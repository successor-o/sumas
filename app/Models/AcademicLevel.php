<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicLevel extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
