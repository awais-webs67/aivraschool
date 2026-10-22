<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanRequest;
use App\Models\Plan;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->withCount('schools')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan([
            'billing_model' => 'monthly',
            'trial_days' => config('skooline.default_trial_days'),
            'max_branches' => 1,
            'storage_gb' => 5,
            'is_active' => true,
            'show_on_website' => true,
            'modules' => ['students', 'attendance', 'fees', 'exams', 'communication', 'reports'],
        ])]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->planData());
        Activity::log('plan.created', "Created plan {$plan->name}", $plan);

        return redirect()->route('admin.plans.index')->with('success', "Plan \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', ['plan' => $plan]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planData());
        Activity::log('plan.updated', "Updated plan {$plan->name}", $plan);

        return redirect()->route('admin.plans.index')->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->schools()->exists()) {
            return back()->with('error', 'This plan is used by schools. Deactivate it instead of deleting.');
        }

        $plan->delete();
        Activity::log('plan.deleted', "Deleted plan {$plan->name}");

        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted.');
    }
}
