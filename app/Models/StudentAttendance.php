<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    use BelongsToSchool;

    protected $table = 'student_attendance';

    protected $guarded = ['id', 'school_id'];

    public const STATUSES = ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave', 'half_day' => 'Half day'];

    /** One letter per status for registers. */
    public const SHORT = ['present' => 'P', 'absent' => 'A', 'late' => 'L', 'leave' => 'Lv', 'half_day' => 'H'];

    /** Statuses that count as attended when working out a percentage. */
    public const ATTENDED = ['present', 'late', 'half_day'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public static function badge(string $status): string
    {
        return match ($status) {
            'present' => 'badge-success',
            'absent' => 'badge-danger',
            'late', 'half_day' => 'badge-warning',
            default => 'badge-neutral',
        };
    }
}
