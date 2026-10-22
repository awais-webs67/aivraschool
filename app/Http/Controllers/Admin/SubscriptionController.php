<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate([
            'payment_status' => ['nullable', Rule::in(Subscription::PAYMENT_STATUSES)],
        ])['payment_status'] ?? null;

        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::query()
                ->with('school')
                ->when($status, fn ($q) => $q->where('payment_status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function store(StoreSubscriptionRequest $request, School $school, SubscriptionManager $manager): RedirectResponse
    {
        $data = $request->validated();

        $manager->assign($school, Plan::findOrFail($data['plan_id']), $data, $request->user()->id);

        return redirect()->route('admin.schools.show', $school)->with('success', 'Subscription added. The school is active.');
    }

    public function markPaid(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(Subscription::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $subscription->update($data + ['payment_status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Payment recorded.');
    }
}
