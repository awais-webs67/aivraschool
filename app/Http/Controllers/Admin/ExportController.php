<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\School;
use App\Support\Activity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV downloads that open directly in Excel.
 */
class ExportController extends Controller
{
    public function schools(): StreamedResponse
    {
        $rows = School::query()->with('plan')->withCount('users')->orderBy('name')->lazy()->map(fn (School $s) => [
            $s->id, $s->name, $s->displayUrl(), $s->status->label(), $s->plan?->name, $s->accessEndsAt()?->format('Y-m-d'),
            $s->principal_name, $s->email, $s->phone, $s->city, $s->country, $s->currencyCode(), $s->users_count, $s->created_at->format('Y-m-d'),
        ]);

        return $this->csv('schools', ['ID', 'School', 'Portal', 'Status', 'Plan', 'Access until', 'Principal', 'Email', 'Phone', 'City', 'Country', 'Currency', 'Users', 'Created'], $rows);
    }

    public function subscriptions(Request $request): StreamedResponse
    {
        $filters = $request->only(['payment_status', 'q', 'from', 'to']);

        $rows = SubscriptionController::filtered($filters)->with('school')->latest('id')->lazy()->map(fn ($s) => [
            $s->invoice_number, $s->school?->name, $s->plan_name, $s->billing_model, $s->starts_at->format('Y-m-d'), $s->ends_at?->format('Y-m-d'),
            $s->student_count, $s->amount, $s->payment_status, $s->payment_method, $s->payment_reference, $s->paid_at?->format('Y-m-d'),
        ]);

        return $this->csv('invoices', ['Invoice', 'School', 'Plan', 'Billing', 'Starts', 'Ends', 'Students', 'Amount', 'Payment status', 'Method', 'Reference', 'Paid on'], $rows);
    }

    public function leads(Request $request): StreamedResponse
    {
        $rows = DemoRequestController::filtered($request->only(['status', 'q', 'follow_up']))->latest()->lazy()->map(fn (DemoRequest $l) => [
            $l->created_at->format('Y-m-d'), $l->name, $l->school_name, $l->phone, $l->email, $l->city, $l->students,
            DemoRequest::STATUS_LABELS[$l->status] ?? $l->status, $l->follow_up_on?->format('Y-m-d'), $l->message, $l->notes,
        ]);

        return $this->csv('leads', ['Received', 'Name', 'School', 'Phone', 'Email', 'City', 'Students', 'Status', 'Follow up', 'Message', 'Notes'], $rows);
    }

    protected function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        Activity::log('export.'.$name, 'Exported '.$name.' to CSV');

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                // Guard against spreadsheet formula injection from user-entered text.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^(=|@|[+\-](?![\d\s(]))/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, 'skooline-'.$name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
