<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Plan;
use App\Models\School;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::query()->with(['plan', 'school'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', ['announcement' => new Announcement(['tone' => 'info', 'audience' => 'all', 'is_active' => true])] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $announcement = Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);
        Activity::log('announcement.created', "Published announcement \"{$announcement->title}\" to {$announcement->audienceLabel()}", $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement saved.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', ['announcement' => $announcement] + $this->options());
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));
        Activity::log('announcement.updated', "Updated announcement \"{$announcement->title}\"", $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();
        Activity::log('announcement.deleted', "Deleted announcement \"{$announcement->title}\"");

        return back()->with('success', 'Announcement deleted.');
    }

    protected function options(): array
    {
        return [
            'plans' => Plan::query()->ordered()->pluck('name', 'id'),
            'schools' => School::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'tone' => ['required', Rule::in(array_keys(Announcement::TONES))],
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'plan_id' => ['nullable', 'required_if:audience,plan', 'exists:plans,id'],
            'school_id' => ['nullable', 'required_if:audience,school', 'exists:schools,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['plan_id'] = $data['audience'] === 'plan' ? $data['plan_id'] : null;
        $data['school_id'] = $data['audience'] === 'school' ? $data['school_id'] : null;

        return $data;
    }
}
