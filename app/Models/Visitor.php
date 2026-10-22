<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['in_at' => 'datetime', 'out_at' => 'datetime'];
    }
}
