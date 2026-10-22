<?php

namespace App\Http\Controllers\School\Money;

use App\Http\Controllers\Controller;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Services\School\FeeService;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fees: overview, fee setup, monthly vouchers, voucher list / printing and defaulters.
 */
class FeeController extends Controller
{
    public function index(): View
    {
        $month = today()->startOfMonth();
        $thisMonth = FeeInvoice::query()->where('status', '!=', 'cancelled')->whereDate('month', $month);

        return view('school.fees.index', [
            'month' => $month,
            'billed' => (float) (clone $thisMonth)->sum('total'),
            'collectedMonth' => (float) FeePayment::query()->whereBetween('paid_on', [$month->toDateString(), today()->toDateString()])->sum('amount'),
            'collectedToday' => (float) FeePayment::query()->whereDate('paid_on', today())->sum('amount'),
            'outstanding' => (float) FeeInvoice::query()->open()->sum(DB::raw('total - paid')),
            'defaulters' => FeeInvoice::query()->open()->whereDate('due_date', '<', today())->distinct()->count('student_id'),
            'generated' => (clone $thisMonth)->count(),
            'students' => Student::query()->active()->count(),
            'recent' => FeePayment::query()->with('student')->latest('id')->limit(8)->get(),
            'hasSetup' => FeeStructure::query()->exists(),
        ]);
    }

    /** Fee heads, amounts per class, due day and late fee on one screen. */
    public function setup(): View
    {
        if (! FeeHead::query()->exists()) {
            foreach (FeeHead::STANDARD as $i => [$name, $frequency]) {
                FeeHead::create(['name' => $name, 'frequency' => $frequency, 'sort_order' => $i]);
            }
        }

        return view('school.fees.setup', [
            'heads' => FeeHead::query()->orderBy('sort_order')->orderBy('name')->get(),
            'classes' => SchoolClass::query()->ordered()->get(),
            'amounts' => FeeStructure::query()->get()->mapWithKeys(fn ($r) => [$r->class_id.'-'.$r->fee_head_id => (float) $r->amount]),
            'dueDay' => FeeService::dueDay(),
            'lateFee' => FeeService::lateFee(),
        ]);
    }

    public function saveSetup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['array'],
            'amount.*.*' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'late_fee' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);
        $classIds = SchoolClass::query()->pluck('id')->all();
        $headIds = FeeHead::query()->pluck('id')->all();

        DB::transaction(function () use ($data, $classIds, $headIds) {
            foreach ($data['amount'] ?? [] as $classId => $row) {
                foreach ($row as $headId => $amount) {
                    if (! in_array((int) $classId, $classIds, true) || ! in_array((int) $headId, $headIds, true)) {
                        continue;
                    }
                    $amount === null || (float) $amount == 0.0
                        ? FeeStructure::query()->where('class_id', $classId)->where('fee_head_id', $headId)->delete()
                        : FeeStructure::updateOrCreate(['class_id' => (int) $classId, 'fee_head_id' => (int) $headId], ['amount' => (float) $amount]);
                }
            }
        });

        current_school()->putSettings(['fees.due_day' => (int) $data['due_day'], 'fees.late_fee' => (float) ($data['late_fee'] ?? 0)]);
        Activity::log('fees.setup', 'Updated fee structure');

        return back()->with('success', 'Fee structure saved.');
    }

    public function storeHead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('fee_heads')->where('school_id', current_school()->id)],
            'frequency' => ['required', Rule::in(array_keys(FeeHead::FREQUENCIES))],
        ]);
        FeeHead::create($data + ['sort_order' => (int) FeeHead::query()->max('sort_order') + 1]);

        return back()->with('success', "{$data['name']} added.");
    }

    public function destroyHead(FeeHead $head): RedirectResponse
    {
        $head->delete();

        return back()->with('success', "{$head->name} removed. Vouchers already made keep it.");
    }

    public function generateForm(): View
    {
        return view('school.fees.generate', [
            'heads' => FeeHead::query()->orderBy('sort_order')->get(),
            'groups' => Section::grouped(),
            'dueDay' => FeeService::dueDay(),
        ]);
    }

    public function generate(Request $request, FeeService $fees): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'heads' => ['required', 'array', 'min:1'],
            'heads.*' => ['integer'],
            'section_id' => ['nullable', 'integer'],
        ], ['heads.required' => 'Tick at least one fee to include.']);

        $students = Student::query()->active()->when($data['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))->get();
        $result = $fees->generateMonth($students, Carbon::createFromFormat('!Y-m', $data['month']), Carbon::parse($data['due_date']), $data['heads'], $request->user('school')->id);

        Activity::log('fees.generated', "Generated {$result['created']} fee vouchers for ".Carbon::createFromFormat('!Y-m', $data['month'])->format('F Y'));

        return redirect()->route('school.fees.invoices', ['month' => $data['month']])->with('success', "{$result['created']} vouchers made."
            .($result['skipped'] ? " {$result['skipped']} skipped (already have one for this month, or no fee set for their class)." : ''));
    }

    public function invoices(Request $request): View
    {
        $filters = $this->invoiceFilters($request);

        return view('school.fees.invoices', [
            'invoices' => $this->filteredInvoices($filters)->with('student.schoolClass', 'student.section')->latest('due_date')->latest('id')->paginate(30)->withQueryString(),
            'filters' => $filters,
            'groups' => Section::grouped(),
        ]);
    }

    /** Printable vouchers (3 copies each: bank, school, parent). */
    public function print(Request $request): View
    {
        $filters = $this->invoiceFilters($request);
        $invoices = $this->filteredInvoices($filters)->with('items', 'student.schoolClass', 'student.section')->orderBy('student_id')->limit(500)->get();
        $arrears = FeeInvoice::query()->open()->whereIn('student_id', $invoices->pluck('student_id'))
            ->get()->groupBy('student_id');

        return view('school.fees.print', ['invoices' => $invoices, 'arrears' => $arrears, 'school' => current_school()]);
    }

    public function defaulters(Request $request): View|StreamedResponse
    {
        $sectionId = $request->integer('section_id') ?: null;
        $rows = FeeInvoice::query()->open()->whereDate('due_date', '<', today())
            ->when($sectionId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section_id', $sectionId)))
            ->selectRaw('student_id, sum(total - paid) as owed, count(*) as vouchers, min(due_date) as since')
            ->groupBy('student_id')->orderByDesc('owed')->get();
        $students = Student::query()->with('schoolClass', 'section')->whereIn('id', $rows->pluck('student_id'))->get()->keyBy('id');

        if ($request->query('export')) {
            return response()->streamDownload(function () use ($rows, $students) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Admission no', 'Name', 'Class', 'Father', 'Phone', 'Vouchers', 'Owed', 'Unpaid since']);
                foreach ($rows as $r) {
                    $s = $students[$r->student_id] ?? null;
                    fputcsv($out, [$s?->admission_no, $s?->name, $s?->classLabel(), $s?->father_name, $s?->parentPhone(), $r->vouchers, round((float) $r->owed, 2), $r->since]);
                }
                fclose($out);
            }, 'fee-defaulters-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('school.fees.defaulters', ['rows' => $rows, 'students' => $students, 'groups' => Section::grouped(), 'sectionId' => $sectionId]);
    }

    protected function invoiceFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'section_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([...array_keys(FeeInvoice::STATUSES), 'open'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
    }

    protected function filteredInvoices(array $filters)
    {
        return FeeInvoice::query()
            ->when($filters['month'] ?? null, fn ($q, $m) => $q->whereDate('month', Carbon::createFromFormat('!Y-m', $m)))
            ->when($filters['section_id'] ?? null, fn ($q, $id) => $q->whereHas('student', fn ($s) => $s->where('section_id', $id)))
            ->when(($filters['status'] ?? null) === 'open', fn ($q) => $q->open())
            ->when(in_array($filters['status'] ?? null, array_keys(FeeInvoice::STATUSES), true), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('invoice_no', 'like', "%{$t}%")
                ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$t}%")->orWhere('admission_no', 'like', "%{$t}%"))));
    }
}
