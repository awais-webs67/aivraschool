<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * AI assistant questions and tokens used by one school in one month.
 */
class AiUsage extends Model
{
    protected $table = 'ai_usage';

    protected $guarded = ['id'];

    public static function forMonth(School $school, ?string $month = null): ?self
    {
        return static::query()->where('school_id', $school->id)->where('month', $month ?? now()->format('Y-m'))->first();
    }

    public static function requestsThisMonth(School $school): int
    {
        return (int) static::forMonth($school)?->requests;
    }

    /** Count one answered question (row is created on the first one of the month). */
    public static function record(School $school, int $inputTokens, int $outputTokens): void
    {
        $month = now()->format('Y-m');

        DB::transaction(function () use ($school, $month, $inputTokens, $outputTokens) {
            $row = static::query()->lockForUpdate()->firstOrCreate(['school_id' => $school->id, 'month' => $month]);
            $row->increment('requests', 1, [
                'input_tokens' => $row->input_tokens + $inputTokens,
                'output_tokens' => $row->output_tokens + $outputTokens,
            ]);
        });
    }
}
