<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['support_ticket_id', 'author_type', 'author_id', 'author_name', 'body', 'created_at'])]
class TicketMessage extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id')->withoutGlobalScopes();
    }

    public function fromPlatform(): bool
    {
        return $this->author_type === 'platform';
    }
}
