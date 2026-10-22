<?php

namespace App\Models;

use App\Enums\SchoolStatus;
use App\Support\Tenancy\CurrentSchool;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A subscribing school. All schools share one database; every school-owned
 * row carries this school's id (see BelongsToSchool).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SchoolStatus $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_ends_at
 */
#[Fillable([
    'name', 'slug', 'email', 'phone', 'city', 'country', 'currency', 'address', 'logo_path', 'photo_path', 'status', 'student_count',
    'principal_name', 'principal_email', 'principal_phone', 'principal_national_id', 'principal_qualification',
    'principal_address', 'principal_bio', 'principal_photo_path', 'ai_requests_limit',
    'plan_id', 'trial_ends_at', 'subscription_ends_at', 'suspended_at', 'suspension_reason', 'notes', 'expiry_reminded_for', 'settings',
])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SchoolStatus::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('starts_at')->latest('id');
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('starts_at');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class)->withoutGlobalScopes();
    }

    /** Portal URL of this school, e.g. "https://skooline.com/greenfield/login". */
    public function url(string $path = ''): string
    {
        return url($this->slug.($path !== '' ? '/'.ltrim($path, '/') : ''));
    }

    /** AI assistant questions allowed per month: the school's own limit, else its plan's. */
    public function aiMonthlyLimit(): int
    {
        if (! $this->hasModule('ai')) {
            return 0;
        }

        return (int) ($this->ai_requests_limit ?? $this->plan?->ai_requests_monthly ?? 0);
    }

    /** The currency this school's fees are charged in. */
    public function currencyCode(): string
    {
        return $this->currency ?: Setting::currency();
    }

    /** Address shown to people, e.g. "skooline.com/greenfield". */
    public function displayUrl(): string
    {
        return preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')).'/'.$this->slug;
    }

    /**
     * Run code as this school (queries scoped to it, new rows stamped with it).
     *
     * @template T
     *
     * @param  callable(School): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        return app(CurrentSchool::class)->runFor($this, $callback);
    }

    public function hasModule(string $module): bool
    {
        return (bool) $this->plan?->hasModule($module);
    }

    /** Date until which the school can use the system (null = no end, e.g. lifetime). */
    public function accessEndsAt(): ?Carbon
    {
        return $this->status === SchoolStatus::Trial ? $this->trial_ends_at : $this->subscription_ends_at;
    }

    public function inGracePeriod(): bool
    {
        $ends = $this->accessEndsAt();

        return $this->status === SchoolStatus::Active
            && $ends !== null
            && $ends->isPast()
            && $ends->copy()->addDays(config('skooline.grace_days'))->isFuture();
    }

    public function canAccess(): bool
    {
        if (in_array($this->status, [SchoolStatus::Suspended, SchoolStatus::Expired], true)) {
            return false;
        }

        $ends = $this->accessEndsAt();

        if ($ends === null) {
            return true;
        }

        $grace = $this->status === SchoolStatus::Active ? config('skooline.grace_days') : 0;

        return $ends->copy()->addDays($grace)->isFuture();
    }

    public function daysLeft(): ?int
    {
        $ends = $this->accessEndsAt();

        return $ends ? (int) now()->startOfDay()->diffInDays($ends->copy()->startOfDay(), false) : null;
    }

    /** Where invoices and reminders go: principal, school email, else the first portal admin. */
    public function billingEmails(): array
    {
        $emails = array_values(array_unique(array_filter([$this->principal_email, $this->email])));

        return $emails ?: array_filter([User::withoutGlobalScopes()->where('school_id', $this->id)->where('role', 'admin')->value('email')]);
    }

    /** Per-school option from the settings column (e.g. "fees.due_day"). */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    /** Saves options into the settings column (merged with what is already there). */
    public function putSettings(array $values): void
    {
        $settings = $this->settings ?? [];
        foreach ($values as $key => $value) {
            data_set($settings, $key, $value);
        }
        $this->forceFill(['settings' => $settings])->save();
    }
}
