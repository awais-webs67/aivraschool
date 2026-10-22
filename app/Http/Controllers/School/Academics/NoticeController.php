<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Section;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** School notice board: everyone reads; principal, vice principal and admin post. */
class NoticeController extends Controller
{
    public function index(Request $request): View
    {
        $canPost = $request->user('school')->allows('notices');

        return view('school.notices.index', [
            'notices' => Notice::query()->with('section.schoolClass', 'author')
                ->when(! $canPost, fn ($q) => $q->live()->forStaff())
                ->orderByDesc('is_pinned')->latest('publish_on')->latest('id')->paginate(20),
            'canPost' => $canPost,
            'groups' => Section::grouped(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'audience' => ['required', Rule::in(array_keys(Notice::AUDIENCES))],
            'section_id' => ['required_if:audience,section', 'nullable', Rule::exists('sections', 'id')->where('school_id', current_school()->id)],
            'publish_on' => ['required', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:publish_on'],
            'attachment' => Uploads::FILE_RULES,
        ], ['section_id.required_if' => 'Choose the class.']);
        $file = $request->file('attachment');

        $notice = Notice::create(collect($data)->except('attachment')->all() + [
            'section_id' => $data['audience'] === 'section' ? $data['section_id'] : null,
            'is_pinned' => $request->boolean('is_pinned'),
            'attachment_path' => Uploads::store($file, 'notices'),
            'attachment_name' => $file?->getClientOriginalName(),
            'created_by' => $request->user('school')->id,
        ]);
        Activity::log('notice.created', "Posted notice: {$notice->title}", $notice);

        return back()->with('success', 'Notice posted.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        if ($notice->attachment_path) {
            Storage::delete($notice->attachment_path);
        }
        $notice->delete();

        return back()->with('success', 'Notice deleted.');
    }

    public function attachment(Request $request, Notice $notice): Response
    {
        $user = $request->user('school');
        if ($user->isParent()) {
            $sections = $user->children()->pluck('section_id')->filter()->all();
            abort_unless(Notice::query()->live()->forParents($sections)->whereKey($notice->id)->exists(), 404);
        }

        abort_unless($notice->attachment_path && Storage::exists($notice->attachment_path), 404);

        return Storage::download($notice->attachment_path, $notice->attachment_name ?? 'attachment');
    }
}
