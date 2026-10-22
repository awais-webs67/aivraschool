<?php

namespace App\Support\Tenancy;

use App\Models\School;

/**
 * Holds the school the current request (or job) is working for.
 *
 * Set once per request by the IdentifySchool middleware. Every model using
 * BelongsToSchool reads it to scope queries and to stamp new rows.
 */
class CurrentSchool
{
    protected ?School $school = null;

    public function set(?School $school): void
    {
        $this->school = $school;
    }

    public function get(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school?->id;
    }

    public function check(): bool
    {
        return $this->school !== null;
    }

    /**
     * Run a callback as if the given school were current, then restore the previous one.
     *
     * @template T
     *
     * @param  callable(School): T  $callback
     * @return T
     */
    public function runFor(School $school, callable $callback): mixed
    {
        $previous = $this->school;
        $this->school = $school;

        try {
            return $callback($school);
        } finally {
            $this->school = $previous;
        }
    }
}
