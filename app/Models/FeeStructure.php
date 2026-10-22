<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Amount of one fee head for one class. */
#[Fillable(['class_id', 'fee_head_id', 'amount'])]
class FeeStructure extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
}
