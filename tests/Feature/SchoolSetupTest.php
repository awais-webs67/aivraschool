<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolSetupTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->createSchool('greenfield');
        $this->actingAs($this->school->users()->first(), 'school');
    }

    private function url(string $path = ''): string
    {
        return $this->schoolUrl('greenfield', 'setup/'.$path);
    }

    public function test_new_school_starts_with_a_current_session(): void
    {
        $current = $this->school->run(fn () => AcademicSession::current());

        $this->assertNotNull($current);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $current->name);
    }

    public function test_setup_pages_render_for_admin(): void
    {
        foreach (['profile', 'sessions', 'classes', 'subjects'] as $page) {
            $this->get($this->url($page))->assertOk();
        }

        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk()->assertSee('Set up your school');
    }

    public function test_teachers_cannot_open_setup(): void
    {
        $teacher = $this->school->run(fn () => User::create(['name' => 'T', 'email' => 't@g.test', 'password' => 'x', 'role' => 'teacher']));
        $this->actingAs($teacher, 'school');

        $this->get($this->url('classes'))->assertForbidden();
        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk()->assertDontSee('Set up your school');
    }

    public function test_profile_and_logo_can_be_updated(): void
    {
        Storage::fake('local');

        $this->put($this->url('profile'), [
            'name' => 'Greenfield Grammar School', 'currency' => 'USD',
            'phone' => '042-111222',
            'address' => 'Johar Town, Lahore',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect()->assertSessionHas('success');

        $school = $this->school->fresh();
        $this->assertSame('Greenfield Grammar School', $school->name);
        $this->assertStringStartsWith("schools/{$school->id}/", $school->logo_path);
        Storage::disk('local')->assertExists($school->logo_path);

        $this->get($this->schoolUrl('greenfield', 'logo'))->assertOk();

        $this->put($this->url('profile'), ['name' => 'Greenfield Grammar School', 'currency' => 'USD', 'remove_logo' => '1']);
        $this->assertNull($this->school->fresh()->logo_path);
    }

    public function test_logo_upload_rejects_non_images(): void
    {
        $this->put($this->url('profile'), [
            'name' => 'Greenfield',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_sessions_only_one_is_current(): void
    {
        $this->post($this->url('sessions'), ['name' => '2030-31', 'starts_on' => '2030-04-01', 'ends_on' => '2031-03-31', 'is_current' => '1'])
            ->assertSessionHas('success');

        $this->school->run(function () {
            $this->assertSame('2030-31', AcademicSession::current()->name);
            $this->assertSame(1, AcademicSession::where('is_current', true)->count());
        });

        $old = $this->school->run(fn () => AcademicSession::where('name', '!=', '2030-31')->first());
        $this->post($this->url("sessions/{$old->id}/current"))->assertSessionHas('success');
        $this->assertTrue($old->fresh()->is_current);

        $this->delete($this->url("sessions/{$old->id}"))->assertSessionHas('error');
        $this->assertModelExists($old);
    }

    public function test_session_dates_are_validated(): void
    {
        $this->post($this->url('sessions'), ['name' => 'Bad', 'starts_on' => '2030-04-01', 'ends_on' => '2030-01-01'])
            ->assertSessionHasErrors('ends_on');
    }

    public function test_standard_classes_are_added_once_with_section_a(): void
    {
        $this->post($this->url('classes/standard'))->assertSessionHas('success', '13 classes added.');
        $this->post($this->url('classes/standard'))->assertSessionHas('success', 'All standard classes already exist.');

        $this->school->run(function () {
            $this->assertSame(13, SchoolClass::count());
            $this->assertSame(13, Section::where('name', 'A')->count());
            $this->assertSame('Playgroup', SchoolClass::ordered()->first()->name);
        });
    }

    public function test_class_and_section_management(): void
    {
        $this->post($this->url('classes'), ['name' => 'O Level 1'])->assertSessionHas('success');
        $class = $this->school->run(fn () => SchoolClass::where('name', 'O Level 1')->firstOrFail());

        $this->post($this->url("classes/{$class->id}/sections"), ['name' => 'B', 'capacity' => 30])->assertSessionHas('success');
        $this->post($this->url("classes/{$class->id}/sections"), ['name' => 'B'])->assertSessionHasErrors('name');
        $this->post($this->url('classes'), ['name' => 'O Level 1'])->assertSessionHasErrors('name');

        $sections = $this->school->run(fn () => $class->sections()->get());
        $this->assertSame(['A', 'B'], $sections->pluck('name')->all());

        $this->delete($this->url("sections/{$sections[1]->id}"))->assertSessionHas('success');
        $this->delete($this->url("sections/{$sections[0]->id}"))->assertSessionHas('error'); // last section stays

        $this->put($this->url("classes/{$class->id}"), ['name' => 'O Level I', 'sort_order' => 20])->assertSessionHas('success');
        $this->assertSame('O Level I', $class->fresh()->name);

        $this->delete($this->url("classes/{$class->id}"))->assertSessionHas('success');
        $this->assertModelMissing($class);
    }

    public function test_subjects_with_classes(): void
    {
        $this->post($this->url('classes/standard'));
        $classIds = $this->school->run(fn () => SchoolClass::whereIn('name', ['Class 9', 'Class 10'])->pluck('id')->all());

        $this->post($this->url('subjects'), ['name' => 'Physics', 'code' => 'PHY', 'type' => 'both', 'class_ids' => $classIds])
            ->assertSessionHas('success');

        $physics = $this->school->run(fn () => Subject::where('name', 'Physics')->firstOrFail());
        $this->assertEqualsCanonicalizing($classIds, $this->school->run(fn () => $physics->classes()->pluck('classes.id')->all()));

        $this->post($this->url('subjects/standard'))->assertSessionHas('success');
        $this->assertSame(count(Subject::STANDARD), $this->school->run(fn () => Subject::count())); // Physics was already there

        $this->put($this->url("subjects/{$physics->id}"), ['name' => 'Physics', 'type' => 'theory', 'class_ids' => []])->assertSessionHas('success');
        $this->assertSame(0, $this->school->run(fn () => $physics->classes()->count()));
    }

    public function test_a_school_cannot_touch_another_schools_setup(): void
    {
        $riverside = $this->createSchool('riverside', adminEmail: 'admin@riverside.test');
        $theirClass = $riverside->run(fn () => SchoolClass::create(['name' => 'Class 5']));
        $theirSubject = $riverside->run(fn () => Subject::create(['name' => 'Urdu', 'type' => 'theory']));

        // Greenfield admin (signed in) tries Riverside's ids through Greenfield's address.
        $this->put($this->url("classes/{$theirClass->id}"), ['name' => 'Hacked'])->assertNotFound();
        $this->delete($this->url("classes/{$theirClass->id}"))->assertNotFound();
        $this->delete($this->url("subjects/{$theirSubject->id}"))->assertNotFound();
        $this->post($this->url('subjects'), ['name' => 'Maths', 'type' => 'theory', 'class_ids' => [$theirClass->id]])
            ->assertSessionHasErrors('class_ids.0');

        $this->assertSame('Class 5', $theirClass->fresh()->name);

        // Same class name is fine in a different school.
        $this->post($this->url('classes'), ['name' => 'Class 5'])->assertSessionHas('success');
    }
}
