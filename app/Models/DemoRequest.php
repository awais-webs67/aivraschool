<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable(['name', 'school_name', 'email', 'phone', 'city', 'students', 'message', 'status', 'ip_address'])]
class DemoRequest extends Model
{
    use CentralConnection;

    public const STATUSES = ['new', 'contacted', 'converted', 'closed'];
}
