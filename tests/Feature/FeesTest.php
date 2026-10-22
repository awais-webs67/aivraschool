<?php

namespace Tests\Feature;

use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\School\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: Student, 2: Student, 3: FeeHead} */
    protected function setUpFees(): array
    {
        $school = $this->fullSchool();

        return [$school, ...$school->run(function () {
            $class = SchoolClass::create(['name' => 'Class 1']);
            $section = $class->sections()->create(['name' => 'A']);
            $tuition = FeeHead::create(['name' => 'Tuition fee', 'frequency' => 'monthly']);
            $exam = FeeHead::create(['name' => 'Exam fee', 'frequency' => 'yearly']);
            FeeStructure::create(['class_id' => $class->id, 'fee_head_id' => $tuition->id, 'amount' => 3000]);
            FeeStructure::create(['class_id' => $class->id, 'fee_head_id' => $exam->id, 'amount' => 500]);
            $ali = Student::create(['name' => 'Ali', 'class_id' => $class->id, 'section_id' => $section->id, 'father_phone' => '03001112222']);
            $sara = Student::create(['name' => 'Sara', 'class_id' => $class->id, 'section_id' => $section->id, 'fee_discount' => 50, 'father_phone' => '03009998888']);

            return [$ali, $sara, $tuition];
        })];
    }

    public function test_setup_saves_amounts_and_settings(): void
    {
        [$school] = $this->setUpFees();
        $this->actingAsRole($school, 'accountant');
        $class = $school->run(fn () => SchoolClass::first());
        $head = $school->run(fn () => FeeHead::where('name', 'Tuition fee')->first());

        $this->get($this->portal('fees/setup'))->assertOk()->assertSee('Tuition fee');
        $this->put($this->portal('fees/setup'), ['amount' => [$class->id => [$head->id => 3500]], 'due_day' => 5, 'late_fee' => 200])->assertRedirect();

        $this->assertEquals(3500, FeeStructure::withoutGlobalScopes()->where('fee_head_id', $head->id)->value('amount'));
        $this->assertSame(5, $school->fresh()->setting('fees.due_day'));
    }

    public function test_monthly_vouchers_apply_discount_and_are_not_duplicated(): void
    {
        [$school, $ali, $sara, $tuition] = $this->setUpFees();
        $this->actingAsRole($school);

        $payload = ['month' => today()->format('Y-m'), 'due_date' => today()->addDays(5)->toDateString(), 'heads' => [$tuition->id]];
        $this->post($this->portal('fees/generate'), $payload)->assertRedirect()->assertSessionHas('success');
        $this->post($this->portal('fees/generate'), $payload)->assertSessionHas('success', fn ($m) => str_contains($m, '0 vouchers') && str_contains($m, '2 skipped'));

        $this->assertEquals(3000, FeeInvoice::withoutGlobalScopes()->where('student_id', $ali->id)->value('total'));
        $this->assertEquals(1500, FeeInvoice::withoutGlobalScopes()->where('student_id', $sara->id)->value('total'));
        $this->get($this->portal('fees/vouchers/print?month='.today()->format('Y-m')))->assertOk()->assertSee('Bank copy')->assertSee('Ali');
    }

    public function test_payment_goes_to_oldest_voucher_with_late_fee_and_receipt(): void
    {
        [$school, $ali, , $tuition] = $this->setUpFees();
        $school->putSettings(['fees.late_fee' => 100]);
        $school->run(function () use ($ali, $tuition) {
            $svc = app(FeeService::class);
            $svc->createInvoice($ali, [['fee_head_id' => $tuition->id, 'description' => 'Tuition fee', 'amount' => 3000]], 'Old month', today()->subDays(40), today()->subMonth());
            $svc->createInvoice($ali, [['fee_head_id' => $tuition->id, 'description' => 'Tuition fee', 'amount' => 3000]], 'This month', today()->addDays(5), today());
        });
        $this->actingAsRole($school, 'accountant');

        $this->get($this->portal('fees/students/'.$ali->id))->assertOk()->assertSee('6,000');
        $this->post($this->portal('fees/students/'.$ali->id.'/collect'), ['amount' => 99999, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertSessionHasErrors('amount');
        $this->post($this->portal('fees/students/'.$ali->id.'/collect'), ['amount' => 4100, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertSessionHas('receipt');

        $old = FeeInvoice::withoutGlobalScopes()->where('title', 'Old month')->first();
        $new = FeeInvoice::withoutGlobalScopes()->where('title', 'This month')->first();
        $this->assertSame('paid', $old->status);
        $this->assertEquals(100, $old->fine);
        $this->assertSame('partial', $new->status);
        $this->assertEquals(1000, $new->paid);

        $receipt = FeePayment::withoutGlobalScopes()->value('receipt_no');
        $this->get($this->portal('fees/receipts/'.$receipt))->assertOk()->assertSee('FEE RECEIPT')->assertSee('2,000');
        $this->get($this->portal('fees/defaulters'))->assertOk()->assertSee('No defaulters'); // the open voucher is not due yet
    }

    public function test_teachers_cannot_open_fees(): void
    {
        [$school] = $this->setUpFees();
        $this->actingAsRole($school, 'teacher');

        $this->get($this->portal('fees'))->assertForbidden();
    }
}
