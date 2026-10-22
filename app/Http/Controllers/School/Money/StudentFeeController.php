<?php

namespace App\Http\Controllers\School\Money;

use App\Http\Controllers\Controller;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Services\School\FeeService;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * One student's fees: vouchers, payments, collecting money, receipts and one-off vouchers.
 */
class StudentFeeController extends Controller
{
    /** Find a student at the fee counter. */
    public function search(Request $request): View|RedirectResponse
    {
        $q = trim((string) $request->query('q'));
        $results = $q === '' ? collect() : Student::query()->with('schoolClass', 'section')
            ->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('admission_no', $q)->orWhere('father_phone', 'like', "%{$q}%"))
            ->orderBy('name')->limit(30)->get();

        if ($results->count() === 1) {
            return redirect()->route('school.fees.student', $results->first());
        }

        return view('school.fees.search', ['q' => $q, 'results' => $results]);
    }

    public function show(Student $student): View
    {
        return view('school.fees.student', [
            'student' => $student->load('schoolClass', 'section'),
            'invoices' => FeeInvoice::query()->with('items')->where('student_id', $student->id)->latest('due_date')->latest('id')->limit(36)->get(),
            'payments' => FeePayment::query()->where('student_id', $student->id)->latest('paid_on')->latest('id')->limit(30)->get()->groupBy('receipt_no'),
            'balance' => FeeService::balanceOf($student),
            'lateFee' => FeeService::lateFee(),
            'heads' => FeeHead::query()->orderBy('sort_order')->get(),
            'siblings' => $student->siblings()->filter(fn ($s) => $s->status === 'active')->map(fn ($s) => [$s, FeeService::balanceOf($s)]),
        ]);
    }

    public function collect(Request $request, Student $student, FeeService $fees): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'method' => ['required', Rule::in(array_keys(FeePayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:80'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $payments = $fees->collect($student, (float) $data['amount'], $data['method'], $data['reference'] ?? null, Carbon::parse($data['paid_on']), $request->user('school')->id, ! $request->boolean('waive_fine'));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        $receipt = $payments->first()->receipt_no;
        Activity::log('fees.collected', 'Received '.money($data['amount'])." from {$student->name} (receipt {$receipt})", $student);

        return redirect()->route('school.fees.student', $student)->with('success', 'Payment saved. Receipt '.$receipt.'.')->with('receipt', $receipt);
    }

    public function receipt(string $receipt): View
    {
        $payments = FeePayment::query()->with('invoice', 'student.schoolClass', 'student.section', 'receiver')->where('receipt_no', $receipt)->get();
        abort_if($payments->isEmpty(), 404);

        return view('school.fees.receipt', ['payments' => $payments, 'student' => $payments->first()->student, 'school' => current_school(), 'balance' => FeeService::balanceOf($payments->first()->student)]);
    }

    /** A one-off voucher, e.g. admission fee or a book charge. */
    public function custom(Request $request, Student $student, FeeService $fees): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'due_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:120'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'apply_discount' => ['nullable', 'boolean'],
        ]);
        $lines = collect($data['lines'])->filter(fn ($l) => filled($l['description'] ?? null) && (float) ($l['amount'] ?? 0) > 0)
            ->map(fn ($l) => ['description' => $l['description'], 'amount' => (float) $l['amount'], 'fee_head_id' => FeeHead::query()->where('name', $l['description'])->value('id')])
            ->values()->all();

        if ($lines === []) {
            return back()->withInput()->withErrors(['lines' => 'Add at least one line with an amount.']);
        }

        $invoice = $fees->createInvoice($student, $lines, $data['title'], Carbon::parse($data['due_date']), null, $request->user('school')->id, $request->boolean('apply_discount'));
        Activity::log('fees.voucher', "Made voucher {$invoice->invoice_no} for {$student->name} (".money($invoice->total).')', $invoice);

        return back()->with('success', "Voucher {$invoice->invoice_no} made.");
    }

    /** Cancel a voucher nobody has paid anything on. */
    public function cancel(FeeInvoice $invoice): RedirectResponse
    {
        abort_if((float) $invoice->paid > 0, 422, 'Part of this voucher is paid, so it cannot be cancelled.');
        $invoice->update(['status' => 'cancelled']);
        Activity::log('fees.cancelled', "Cancelled voucher {$invoice->invoice_no}", $invoice);

        return back()->with('success', "Voucher {$invoice->invoice_no} cancelled.");
    }
}
