<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\School\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var School $school */
        $school = tenant();
        $school->loadMissing('plan');

        $modules = collect(config('skooline.modules'))
            ->map(fn (array $module, string $key) => $module + ['key' => $key, 'enabled' => $school->hasModule($key)]);

        return view('school.dashboard', [
            'school' => $school,
            'modules' => $modules,
            'userCount' => User::query()->count(),
        ]);
    }
}
