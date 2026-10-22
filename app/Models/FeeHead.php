<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Something the school charges for, e.g. Tuition fee (monthly) or Admission fee (once). */
#[Fillable(['name', 'frequency', 'sort_order'])]
class FeeHead extends Model
{
    use BelongsToSchool;

    public const FREQUENCIES = ['monthly' => 'Every month', 'yearly' => 'Once a year', 'once' => 'One time (e.g. admission)'];

    public const STANDARD = [['Tuition fee', 'monthly'], ['Admission fee', 'once'], ['Annual charges', 'yearly'], ['Exam fee', 'yearly'], ['Transport fee', 'monthly']];
}
