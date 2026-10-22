<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One diary / homework entry for a section. */
#[Fillable(['section_id', 'subject_id', 'date', 'due_date', 'title', 'details', 'attachment_path', 'attachment_name', 'created_by'])]
class Homework extends Model
{
    use BelongsToSchool;

    protected $table = 'homework';

    protected function casts(): array
    {
        return ['date' => 'date', 'due_date' => 'date'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
