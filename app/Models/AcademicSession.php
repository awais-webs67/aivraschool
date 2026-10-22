<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A school year, e.g. "2026-27". Exactly one session per school is current.
 */
#[Fillable(['name', 'starts_on', 'ends_on', 'is_current'])]
class AcademicSession extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->first();
    }

    /** Make this the school's only current session. */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            static::query()->whereKeyNot($this->id)->update(['is_current' => false]);
            $this->update(['is_current' => true]);
        });
    }

    /** Suggested session for a date: April to March, e.g. "2026-27". */
    public static function suggestFor(Carbon $date): array
    {
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return [
            'name' => $startYear.'-'.substr((string) ($startYear + 1), -2),
            'starts_on' => Carbon::create($startYear, 4, 1)->toDateString(),
            'ends_on' => Carbon::create($startYear + 1, 3, 31)->toDateString(),
        ];
    }
}
