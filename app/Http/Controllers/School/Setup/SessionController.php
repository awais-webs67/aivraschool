<?php

namespace App\Http\Controllers\School\Setup;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(): View
    {
        return view('school.setup.sessions', [
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
            'suggestion' => AcademicSession::suggestFor(now()->addYear()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $session = AcademicSession::create($this->validated($request));

        if ($request->boolean('is_current') || AcademicSession::current() === null) {
            $session->makeCurrent();
        }

        return back()->with('success', "Session {$session->name} added.");
    }

    public function update(Request $request, AcademicSession $session): RedirectResponse
    {
        $session->update($this->validated($request, $session));

        return back()->with('success', "Session {$session->name} updated.");
    }

    public function makeCurrent(AcademicSession $session): RedirectResponse
    {
        $session->makeCurrent();

        return back()->with('success', "{$session->name} is now the current session.");
    }

    public function destroy(AcademicSession $session): RedirectResponse
    {
        if ($session->is_current) {
            return back()->with('error', 'The current session can\'t be deleted. Make another session current first.');
        }

        $session->delete();

        return back()->with('success', "Session {$session->name} deleted.");
    }

    protected function validated(Request $request, ?AcademicSession $session = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:30',
                Rule::unique('academic_sessions')->where('school_id', current_school()->id)->ignore($session?->id),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ]);
    }
}
