<?php

namespace App\Models;

use App\Enums\SchoolStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant;

/**
 * A subscribing school (a tenant). Each school has its own database.
 *
 * @property string $id
 * @property string $name
 * @property string $subdomain
 * @property SchoolStatus $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_ends_at
 */
class School extends Tenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = 'tenants';

    public static function getCustomColumns(): array
    {
        return [
            'id', 'name', 'subdomain', 'email', 'phone', 'city', 'address', 'principal_name',
            'logo_path', 'status', 'plan_id', 'trial_ends_at', 'subscription_ends_at',
            'suspended_at', 'suspension_reason', 'notes', 'created_at', 'updated_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'status' => SchoolStatus::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id')->latest('starts_at')->latest('id');
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id')->latestOfMany('starts_at');
    }

    /** Full hostname, e.g. "greenfield.skooline.com". */
    public function host(): string
    {
        return $this->subdomain.'.'.config('skooline.school_domain');
    }

    /** Public URL of the school's portal, keeping the scheme and port of APP_URL. */
    public function url(string $path = '/'): string
    {
        $app = parse_url((string) config('app.url'));
        $scheme = $app['scheme'] ?? 'https';
        $port = isset($app['port']) ? ':'.$app['port'] : '';

        return $scheme.'://'.$this->host().$port.'/'.ltrim($path, '/');
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
}
