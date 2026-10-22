<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionPayments;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'payment_status' => ['nullable', Rule::in(Subscription::PAYMENT_STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = self::filtered($filters);

        return view('admin.subscriptions.index', [
            'subscriptions' => (clone $query)->with('school')->latest('id')->paginate(20)->withQueryString(),
            'totals' => [
                'invoiced' => (float) (clone $query)->sum('amount'),
                'paid' => (float) (clone $query)->where('payment_status', 'paid')->sum('amount'),
                'unpaid' => (float) (clone $query)->where('payment_status', 'unpaid')->sum('amount'),
                'count' => (clone $query)->count(),
            ],
            'filters' => $filters,
            'status' => $filters['payment_status'] ?? null,
        ]);
    }

    /** Subscriptions matching the list filters (also used by the CSV export). */
    public static function filtered(array $filters): Builder
    {
        return Subscription::query()
            ->when($filters['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('payment_reference', 'like', "%{$term}%")
                ->orWhereHas('school', fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%"))))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', '<=', $d));
    }

    public function store(StoreSubscriptionRequest $request, School $school, SubscriptionManager $manager): RedirectResponse
    {
        $data = $request->validated();

        $subscription = $manager->assign($school, Plan::findOrFail($data['plan_id']), $data, $request->user()->id);
        Activity::log('subscription.added', "Added {$subscription->plan_name} subscription ".money($subscription->amount)." for {$school->name}", $subscription, school: $school);

        return redirect()->route('admin.schools.show', $school)->with('success', 'Subscription added. The school is active.');
    }

    public function markPaid(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(Subscription::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $subscription->update($data + ['payment_status' => 'paid', 'paid_at' => now()]);
        Activity::log('payment.received', "Payment received for {$subscription->invoice_number} (".money($subscription->amount).')', $subscription);

        return back()->with('success', 'Payment recorded.');
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(Subscription::PAYMENT_STATUSES)],
            'payment_method' => ['nullable', Rule::in(Subscription::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['payment_status'] === 'paid') {
            $data['paid_at'] ??= $subscription->paid_at ?? now();
        } elseif ($data['payment_status'] === 'unpaid') {
            $data['paid_at'] = null;
        }

        $subscription->update($data);
        Activity::log('payment.updated', "Invoice {$subscription->invoice_number} marked ".$data['payment_status'], $subscription);

        return back()->with('success', "Invoice {$subscription->invoice_number} updated.");
    }

    /** Emails the invoice (with its view / pay link) to the school. */
    public function send(Subscription $subscription, SubscriptionPayments $payments): RedirectResponse
    {
        if (! $subscription->school) {
            return back()->with('error', 'This school was deleted.');
        }

        if (! $payments->sendInvoice($subscription, $subscription->isOpen() ? 'new' : 'receipt')) {
            return back()->with('error', 'The invoice could not be emailed. Check that the school has an email address and that email is set up (Settings → Email).');
        }

        Activity::log('invoice.sent', "Emailed invoice {$subscription->invoice_number} to {$subscription->school->name}", $subscription, school: $subscription->school);

        return back()->with('success', "Invoice {$subscription->invoice_number} emailed to ".implode(', ', $subscription->school->billingEmails()).'.');
    }

    /** Printable invoice; the browser's "Save as PDF" makes the PDF. */
    public function invoice(Subscription $subscription): View
    {
        return view('admin.subscriptions.invoice', [
            'subscription' => $subscription->load('school'),
            'company' => SettingsController::values(),
        ]);
    }
}
