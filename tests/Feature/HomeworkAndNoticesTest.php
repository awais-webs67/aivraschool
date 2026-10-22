<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeworkAndNoticesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_writes_diary_for_own_class_only(): void
    {
        Storage::fake();
        $school = $this->fullSchool();
        $teacher = $this->actingAsRole($school, 'teacher');
        [$mine, $other] = $school->run(function () use ($teacher) {
            $staff = Staff::create(['name' => 'T', 'user_id' => $teacher->id]);

            return [
                SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A', 'class_teacher_id' => $staff->id]),
                SchoolClass::create(['name' => 'Class 2'])->sections()->create(['name' => 'A']),
            ];
        });

        $this->post($this->portal('homework'), ['section_id' => $other->id, 'title' => 'Nope', 'date' => today()->toDateString()])->assertForbidden();
        $this->post($this->portal('homework'), [
            'section_id' => $mine->id, 'title' => 'Read chapter 2', 'date' => today()->toDateString(),
            'attachment' => UploadedFile::fake()->create('sheet.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $hw = Homework::withoutGlobalScopes()->sole();
        $this->get($this->portal('homework'))->assertOk()->assertSee('Read chapter 2')->assertDontSee('Class 2 – A');
        $this->get($this->portal('homework/'.$hw->id.'/file'))->assertOk()->assertDownload('sheet.pdf');

        $this->actingAsRole($school, 'principal');
        $this->get($this->portal('homework'))->assertSee('No diary yet')->assertSee('Class 2 – A');
    }

    public function test_principal_posts_notices_staff_read_them(): void
    {
        $school = $this->fullSchool();
        $this->actingAsRole($school, 'principal');

        $this->post($this->portal('notices'), ['title' => 'Sports day', 'body' => 'Friday 9am', 'audience' => 'everyone', 'publish_on' => today()->toDateString()])->assertSessionHas('success');
        $this->post($this->portal('notices'), ['title' => 'Later', 'body' => 'x', 'audience' => 'staff', 'publish_on' => today()->addWeek()->toDateString()]);
        $this->post($this->portal('notices'), ['title' => 'Parents', 'body' => 'x', 'audience' => 'parents', 'publish_on' => today()->toDateString()]);
        $this->post($this->portal('notices'), ['title' => 'X', 'body' => 'x', 'audience' => 'section', 'publish_on' => today()->toDateString()])->assertSessionHasErrors('section_id');
        $this->assertSame(3, Notice::withoutGlobalScopes()->count());

        $this->actingAsRole($school, 'teacher');
        $this->get($this->portal('notices'))->assertOk()->assertSee('Sports day')->assertDontSee('Later')->assertDontSee('Post notice');
        $this->post($this->portal('notices'), ['title' => 'T', 'body' => 'x', 'audience' => 'everyone', 'publish_on' => today()->toDateString()])->assertForbidden();
    }
}
