<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var School $school */
        $school = current_school();

        $modules = collect(config('skooline.modules'))
            ->map(fn (array $module, string $key) => $module + ['key' => $key, 'enabled' => $school->hasModule($key)]);

        return view('school.dashboard', [
            'school' => $school,
            'modules' => $modules,
            'userCount' => User::query()->count(),
        ]);
    }
}
