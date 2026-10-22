<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public const AREAS = [
        'school' => 'Schools', 'subscription' => 'Billing', 'payment' => 'Payments', 'plan' => 'Plans', 'lead' => 'Leads',
        'ticket' => 'Support', 'announcement' => 'Announcements', 'team' => 'Team', 'login' => 'Sign-ins',
        'impersonation' => 'Portal access', 'settings' => 'Settings', 'export' => 'Exports',
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'in:'.implode(',', array_keys(self::AREAS))],
            'who' => ['nullable', 'in:platform,school,system'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = ActivityLog::query()
            ->with('school:id,name')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('description', 'like', "%{$t}%")->orWhere('actor_name', 'like', "%{$t}%")))
            ->when($filters['area'] ?? null, fn ($q, $a) => $q->where('action', 'like', $a.'.%'))
            ->when($filters['who'] ?? null, fn ($q, $w) => $q->where('actor_type', $w))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity', ['logs' => $logs, 'filters' => $filters]);
    }
}
