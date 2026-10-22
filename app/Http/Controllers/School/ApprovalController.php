<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Incident;
use App\Models\LeaveRequest;
use Illuminate\View\View;

/** Everything waiting for the principal in one place. */
class ApprovalController extends Controller
{
    public function __invoke(): View
    {
        return view('school.approvals', [
            'leave' => LeaveRequest::query()->with('staff')->where('status', 'pending')->orderBy('from_date')->get(),
            'complaints' => Complaint::query()->where('status', 'open')->latest('id')->limit(10)->get(),
            'serious' => Incident::query()->with('student.schoolClass', 'student.section', 'reporter')->where('kind', 'concern')->where('severity', 'high')
                ->where('date', '>=', today()->subDays(14)->toDateString())->latest('date')->get(),
        ]);
    }

    /** Count shown next to "Approvals" in the menu. */
    public static function waiting(): int
    {
        return LeaveRequest::query()->where('status', 'pending')->count();
    }
}
