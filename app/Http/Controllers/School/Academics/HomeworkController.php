<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\Section;
use App\Models\Subject;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Daily diary / homework. Teachers write it for their sections; the principal sees every section
 * and which ones have no diary yet today.
 */
class HomeworkController extends Controller
{
    public function index(Request $request): View
    {
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString()))->startOfDay(), today(), false);
        $ids = $request->user('school')->teachingSectionIds();
        $sections = Section::query()->with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])->values();
        $sectionId = $request->integer('section') ?: null;

        $homework = Homework::query()->with('section.schoolClass', 'subject', 'author')->whereDate('date', $date)
            ->whereIn('section_id', $sections->pluck('id'))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->latest('id')->get();

        return view('school.homework.index', [
            'date' => $date,
            'sections' => $sections,
            'sectionId' => $sectionId,
            'homework' => $homework->groupBy('section_id'),
            'missing' => $sections->whereNotIn('id', $homework->pluck('section_id'))->values(),
            'subjects' => Subject::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $file = $request->file('attachment');

        Homework::create($data + [
            'attachment_path' => Uploads::store($file, 'homework'),
            'attachment_name' => $file?->getClientOriginalName(),
            'created_by' => $request->user('school')->id,
        ]);

        return redirect()->route('school.homework.index', ['date' => $data['date']])->with('success', 'Diary saved.');
    }

    public function destroy(Request $request, Homework $homework): RedirectResponse
    {
        $user = $request->user('school');
        abort_unless($homework->created_by === $user->id || $user->allows('attendance.all'), 403);
        if ($homework->attachment_path) {
            Storage::delete($homework->attachment_path);
        }
        $homework->delete();

        return back()->with('success', 'Diary entry deleted.');
    }

    public function attachment(Homework $homework): Response
    {
        abort_unless($homework->attachment_path && Storage::exists($homework->attachment_path), 404);

        return Storage::download($homework->attachment_path, $homework->attachment_name ?? 'attachment');
    }

    protected function validated(Request $request): array
    {
        $ids = $request->user('school')->teachingSectionIds();

        $data = $request->validate([
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_id', current_school()->id)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', current_school()->id)],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'title' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:5000'],
            'attachment' => Uploads::FILE_RULES,
        ]);
        abort_if($ids !== null && ! in_array((int) $data['section_id'], $ids, true), 403, 'This is not one of your classes.');

        Activity::log('homework.created', 'Diary added for '.Section::find($data['section_id'])?->label());

        return collect($data)->except('attachment')->all();
    }
}
