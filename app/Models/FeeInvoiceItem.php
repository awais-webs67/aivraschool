<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fee_head_id', 'description', 'amount'])]
class FeeInvoiceItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
