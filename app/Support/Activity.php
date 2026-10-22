<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\PlatformUser;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Writes the audit trail: Activity::log('school.suspended', 'Suspended Greenfield', $school).
 * The actor is whoever is signed in (Super Admin panel first, then the school portal).
 */
class Activity
{
    public static function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?School $school = null): void
    {
        $actor = auth('web')->user() ?? auth('school')->user();

        $school ??= match (true) {
            $subject instanceof School => $subject,
            $subject !== null && isset($subject->school_id) => School::find($subject->school_id),
            default => current_school(),
        };

        try {
            ActivityLog::create([
                'school_id' => $school?->id,
                'actor_type' => match (true) {
                    $actor instanceof PlatformUser => 'platform',
                    $actor instanceof User => 'school',
                    default => 'system',
                },
                'actor_id' => $actor?->getKey(),
                'actor_name' => $actor?->name ?? 'System',
                'action' => $action,
                'description' => mb_substr($description, 0, 255),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e); // never break the user's action because the log failed
        }
    }
}
