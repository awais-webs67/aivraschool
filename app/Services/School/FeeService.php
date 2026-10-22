<?php

namespace App\Services\School;

use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fee vouchers and payments for the current school.
 */
class FeeService
{
    /** Default day of the month a voucher is due, and the late fee after that day. */
    public static function dueDay(): int
    {
        return (int) current_school()->setting('fees.due_day', 10);
    }

    public static function lateFee(): float
    {
        return (float) current_school()->setting('fees.late_fee', 0);
    }

    /**
     * Creates the monthly vouchers for the given students. Students who already have a voucher
     * for that month are skipped, so running it twice is safe.
     *
     * @param  list<int>  $headIds  fee heads to bill (default: every monthly head)
     * @return array{created: int, skipped: int}
     */
    public function generateMonth(Collection $students, Carbon $month, Carbon $dueDate, array $headIds, ?int $userId = null): array
    {
        $month = $month->copy()->startOfMonth();
        $heads = FeeHead::query()->whereIn('id', $headIds)->get()->keyBy('id');
        $structure = FeeStructure::query()->whereIn('fee_head_id', $headIds)->get()->groupBy('class_id');
        $already = FeeInvoice::query()->whereDate('month', $month)->where('status', '!=', 'cancelled')
            ->whereIn('student_id', $students->pluck('id'))->pluck('student_id')->flip();

        $created = $skipped = 0;

        DB::transaction(function () use ($students, $month, $dueDate, $heads, $structure, $already, $userId, &$created, &$skipped) {
            foreach ($students as $student) {
                $lines = ($structure[$student->class_id] ?? collect())
                    ->filter(fn ($row) => (float) $row->amount > 0 && isset($heads[$row->fee_head_id]))
                    ->sortBy(fn ($row) => $heads[$row->fee_head_id]->sort_order)
                    ->map(fn ($row) => ['fee_head_id' => $row->fee_head_id, 'description' => $heads[$row->fee_head_id]->name, 'amount' => (float) $row->amount])
                    ->values()->all();

                if (isset($already[$student->id]) || $lines === []) {
                    $skipped++;

                    continue;
                }

                $this->createInvoice($student, $lines, 'Fee for '.$month->format('F Y'), $dueDate, $month, $userId);
                $created++;
            }
        });

        return compact('created', 'skipped');
    }

    /**
     * One voucher with its lines. The student's fee discount (%) is taken off the total.
     *
     * @param  list<array{fee_head_id?: ?int, description: string, amount: float}>  $lines
     */
    public function createInvoice(Student $student, array $lines, string $title, Carbon $dueDate, ?Carbon $month = null, ?int $userId = null, bool $applyDiscount = true): FeeInvoice
    {
        $subtotal = round(collect($lines)->sum('amount'), 2);
        $discount = $applyDiscount ? round($subtotal * (float) $student->fee_discount / 100, 2) : 0.0;

        $invoice = FeeInvoice::create([
            'student_id' => $student->id,
            'month' => $month?->toDateString(),
            'title' => $title,
            'issue_date' => today()->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'status' => 'unpaid',
            'created_by' => $userId,
        ]);
        $invoice->items()->createMany($lines);

        return $invoice;
    }

    /**
     * Records a payment for a student. The money goes to the oldest open vouchers first.
     * A late fee is added to an overdue voucher the first time it is paid (if set).
     *
     * @return Collection<int, FeePayment>
     */
    public function collect(Student $student, float $amount, string $method, ?string $reference, Carbon $paidOn, ?int $userId, bool $chargeLateFee = true): Collection
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be more than zero.');
        }

        return DB::transaction(function () use ($student, $amount, $method, $reference, $paidOn, $userId, $chargeLateFee) {
            $receipt = FeeInvoice::nextNumber('RC-', 'receipt_no', FeePayment::query());
            $left = round($amount, 2);
            $payments = collect();

            $open = FeeInvoice::query()->open()->where('student_id', $student->id)->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();

            foreach ($open as $invoice) {
                if ($left <= 0) {
                    break;
                }

                if ($chargeLateFee && self::lateFee() > 0 && (float) $invoice->fine == 0.0 && $invoice->due_date->lt($paidOn)) {
                    $invoice->fine = self::lateFee();
                    $invoice->total = round((float) $invoice->total + self::lateFee(), 2);
                }

                $pay = min($left, $invoice->balance());
                if ($pay <= 0) {
                    continue;
                }

                $invoice->paid = round((float) $invoice->paid + $pay, 2);
                $invoice->refreshStatus();
                $invoice->save();
                $left = round($left - $pay, 2);

                $payments->push(FeePayment::create([
                    'receipt_no' => $receipt, 'fee_invoice_id' => $invoice->id, 'student_id' => $student->id, 'amount' => $pay,
                    'method' => $method, 'reference' => $reference, 'paid_on' => $paidOn->toDateString(), 'received_by' => $userId,
                ]));
            }

            if ($payments->isEmpty()) {
                throw new InvalidArgumentException('This student has nothing to pay.');
            }
            if ($left > 0) {
                throw new InvalidArgumentException('The amount is more than this student owes ('.money($amount - $left).'). Nothing was saved.');
            }

            return $payments;
        });
    }

    /** Money still owed by a student across all open vouchers. */
    public static function balanceOf(Student $student): float
    {
        return round((float) FeeInvoice::query()->open()->where('student_id', $student->id)->sum(DB::raw('total - paid')), 2);
    }
}
