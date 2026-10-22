<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Help & support": the school talks to AivraSol. Tickets are scoped to the current school.
 */
class SupportController extends Controller
{
    public function index(): View
    {
        return view('school.support.index', [
            'tickets' => SupportTicket::query()->latest('last_reply_at')->latest('id')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user('school');

        $ticket = DB::transaction(function () use ($data, $user) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'],
            ]);
            $ticket->reply($data['body'], 'school', $user->id, $user->name);

            return $ticket;
        });

        Activity::log('ticket.opened', "Opened {$ticket->number()}: {$ticket->subject}", $ticket);

        return redirect()->route('school.support.show', $ticket)->with('success', 'Your ticket was sent. AivraSol support will reply here.');
    }

    public function show(SupportTicket $ticket): View
    {
        return view('school.support.show', ['ticket' => $ticket->load('messages')]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->isClosed(), 403, 'This ticket is closed. Please open a new one.');

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $user = $request->user('school');

        $ticket->reply($data['body'], 'school', $user->id, $user->name);
        Activity::log('ticket.school_reply', "Replied on {$ticket->number()}", $ticket);

        return back()->with('success', 'Reply sent.');
    }
}
