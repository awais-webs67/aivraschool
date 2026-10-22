<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Models\SupportTicket;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(SupportTicket::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'q' => ['nullable', 'string', 'max:100'],
            'mine' => ['nullable', 'boolean'],
        ]);

        $tickets = $this->query()
            ->with(['school', 'assignee'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($filters['mine'] ?? false, fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('subject', 'like', "%{$term}%")
                ->orWhereHas('school', fn ($q) => $q->where('name', 'like', "%{$term}%"))))
            ->orderByRaw("case status when 'open' then 0 when 'answered' then 1 when 'resolved' then 2 else 3 end")
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->latest('last_reply_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'counts' => $this->query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'filters' => $filters,
        ]);
    }

    public function show(int $ticket): View
    {
        return view('admin.tickets.show', [
            'ticket' => $this->query()->with(['school', 'messages', 'user', 'assignee'])->findOrFail($ticket),
            'staff' => PlatformUser::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function reply(Request $request, int $ticket): RedirectResponse
    {
        $ticket = $this->query()->findOrFail($ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $ticket->reply($data['body'], 'platform', $request->user()->id, $request->user()->name.' (AivraSol)');
        $ticket->assigned_to ??= $request->user()->id;
        $ticket->save();

        Activity::log('ticket.replied', "Replied to {$ticket->number()}: {$ticket->subject}", $ticket);

        return back()->with('success', 'Reply sent to the school.');
    }

    public function update(Request $request, int $ticket): RedirectResponse
    {
        $ticket = $this->query()->findOrFail($ticket);
        $ticket->update($request->validate([
            'status' => ['required', Rule::in(array_keys(SupportTicket::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'assigned_to' => ['nullable', 'exists:platform_users,id'],
        ]));

        Activity::log('ticket.updated', "{$ticket->number()} set to ".SupportTicket::STATUSES[$ticket->status], $ticket);

        return back()->with('success', 'Ticket updated.');
    }

    /** All schools' tickets (the school scope only applies inside a school portal). */
    protected function query(): Builder
    {
        return SupportTicket::query()->withoutGlobalScopes();
    }
}
