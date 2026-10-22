<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A help-desk ticket raised by a school. Scoped to the current school inside the portal;
 * the Super Admin panel reads all tickets with withoutGlobalScopes().
 */
#[Fillable(['school_id', 'user_id', 'subject', 'category', 'priority', 'status', 'assigned_to', 'last_reply_at'])]
class SupportTicket extends Model
{
    use BelongsToSchool;

    public const CATEGORIES = ['general' => 'General question', 'billing' => 'Billing & subscription', 'technical' => 'Technical problem', 'feature' => 'Feature request', 'data' => 'Data import / correction'];

    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];

    public const STATUSES = ['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    protected function casts(): array
    {
        return ['last_reply_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assigned_to');
    }

    public function number(): string
    {
        return 'TKT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'open' => 'badge-brand',
            'answered' => 'badge-info',
            'resolved' => 'badge-success',
            default => 'badge-neutral',
        };
    }

    public function priorityBadge(): string
    {
        return match ($this->priority) {
            'urgent' => 'badge-danger',
            'high' => 'badge-warning',
            'low' => 'badge-neutral',
            default => 'badge-info',
        };
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /** Add a message and move the ticket to the matching status. */
    public function reply(string $body, string $authorType, int $authorId, string $authorName): TicketMessage
    {
        $message = $this->messages()->create([
            'author_type' => $authorType,
            'author_id' => $authorId,
            'author_name' => $authorName,
            'body' => $body,
            'created_at' => now(),
        ]);

        $this->update([
            'status' => $authorType === 'platform' ? 'answered' : 'open',
            'last_reply_at' => now(),
        ]);

        return $message;
    }
}
