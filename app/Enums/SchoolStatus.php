<?php

namespace App\Enums;

enum SchoolStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Badge style used across the admin UI. */
    public function badge(): string
    {
        return match ($this) {
            self::Trial => 'badge-info',
            self::Active => 'badge-success',
            self::Suspended => 'badge-danger',
            self::Expired => 'badge-warning',
        };
    }
}
