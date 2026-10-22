<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'school_name', 'email', 'phone', 'city', 'students', 'message', 'status', 'ip_address'])]
class DemoRequest extends Model
{
    public const STATUSES = ['new', 'contacted', 'converted', 'closed'];
}
