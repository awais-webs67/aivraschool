<?php

namespace App\Models;

use App\Mail\PasswordResetMail;
use Database\Factories\PlatformUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

/**
 * AivraSol platform user (Super Admin and platform staff). Not tied to any school.
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class PlatformUser extends Authenticatable
{
    /** @use HasFactory<PlatformUserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = [
        'super_admin' => 'Owner (Super Admin)',
        'manager' => 'Manager',
        'accountant' => 'Accountant',
        'support' => 'Support',
    ];

    /** What each role may do. The Super Admin may do everything. */
    public const PERMISSIONS = [
        'manager' => ['schools.view', 'schools.manage', 'billing', 'plans', 'leads', 'announcements', 'tickets', 'reports', 'activity', 'impersonate'],
        'accountant' => ['schools.view', 'billing', 'reports'],
        'support' => ['schools.view', 'leads', 'announcements', 'tickets', 'impersonate'],
    ];

    public const PERMISSION_LABELS = [
        'schools.view' => 'View schools',
        'schools.manage' => 'Add, edit, suspend and delete schools',
        'billing' => 'Subscriptions, payments and invoices',
        'plans' => 'Plans and pricing',
        'leads' => 'Demo requests (leads)',
        'announcements' => 'Announcements to schools',
        'tickets' => 'Support tickets',
        'reports' => 'Reports and exports',
        'activity' => 'Activity log',
        'impersonate' => 'Sign in to a school portal as its admin',
        'team' => 'Team members',
        'settings' => 'Platform settings',
        'system' => 'System updates',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    /** Use a one-time recovery code (it is removed so it can't be used twice). */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $code = strtoupper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $this->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$code]))])->save();

        return true;
    }

    /** "Forgot password" email with our own branded message and admin reset link. */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('admin.password.reset', ['token' => $token, 'email' => $this->email]);

        Mail::to($this->email)->send(new PasswordResetMail($this->name, $url));
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isSuperAdmin() || in_array($permission, self::PERMISSIONS[$this->role] ?? [], true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}
