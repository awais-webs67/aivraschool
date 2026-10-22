<?php

namespace App\Http\Controllers\School\People;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Bulk work on students: import from a spreadsheet (CSV) and promote to the next class.
 */
class StudentToolsController extends Controller
{
    public const MAX_IMPORT_ROWS = 2000;

    public function importForm(): View
    {
        return view('school.students.import', ['columns' => Student::IMPORT_COLUMNS, 'classes' => SchoolClass::query()->ordered()->with('sections')->get()]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, Student::IMPORT_COLUMNS);
            fputcsv($out, ['', 'Ali Raza', 'male', '2016-04-12', 'Class 3', 'A', '1', 'Raza Ahmed', '03001234567', '', 'Sana Raza', '', 'House 5, Street 2', 'Lahore', '', now()->toDateString()]);
            fclose($out);
        }, 'students-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Reads the CSV, checks every row first, then saves all rows in one go (nothing is saved if a row is wrong).
     * Classes are matched by name; a missing section is created.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $rows = $this->readCsv($request->file('file')->getRealPath());
        if ($rows === []) {
            return back()->withErrors(['file' => 'The file is empty or the first row is not the column names. Download the template and try again.']);
        }
        if (count($rows) > self::MAX_IMPORT_ROWS) {
            return back()->withErrors(['file' => 'Up to '.self::MAX_IMPORT_ROWS.' students per file. Split the file and import in parts.']);
        }
        if (StudentController::limitReached(count($rows))) {
            return back()->withErrors(['file' => 'This would go over your plan\'s limit of '.StudentController::limit().' students.']);
        }

        $classes = SchoolClass::query()->get()->keyBy(fn ($c) => Str::lower(trim($c->name)));
        $taken = Student::query()->pluck('admission_no')->map(fn ($n) => Str::lower($n))->flip();
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2; // row 1 holds the column names
            if (blank($row['name'] ?? null)) {
                $errors[] = "Row {$line}: name is missing.";
            }
            if (! isset($classes[Str::lower(trim($row['class'] ?? ''))])) {
                $errors[] = "Row {$line}: class \"".($row['class'] ?? '').'" does not exist. Add it under Classes & sections first.';
            }
            if (filled($row['admission_no'] ?? null) && isset($taken[Str::lower($row['admission_no'])])) {
                $errors[] = "Row {$line}: admission no. {$row['admission_no']} is already used.";
            }
            if (filled($row['admission_no'] ?? null)) {
                $taken[Str::lower($row['admission_no'])] = true;
            }
            foreach (['date_of_birth', 'admission_date'] as $field) {
                if (filled($row[$field] ?? null) && $this->date($row[$field]) === null) {
                    $errors[] = "Row {$line}: {$field} \"{$row[$field]}\" is not a date (use 2016-04-12).";
                }
            }
            if (count($errors) >= 20) {
                $errors[] = 'Stopped after 20 problems. Fix these and try again.';
                break;
            }
        }

        if ($errors) {
            return back()->withErrors(['file' => 'Nothing was imported. Please fix these rows:'])->with('import_errors', $errors);
        }

        DB::transaction(function () use ($rows, $classes) {
            foreach ($rows as $row) {
                $class = $classes[Str::lower(trim($row['class']))];
                $section = $class->sections()->firstOrCreate(['name' => strtoupper(trim($row['section'] ?? '')) ?: 'A']);
                $gender = Str::lower(trim($row['gender'] ?? ''));

                Student::create([
                    'admission_no' => trim($row['admission_no'] ?? '') ?: null,
                    'name' => trim($row['name']),
                    'gender' => match ($gender) {
                        'm', 'male', 'boy' => 'male', 'f', 'female', 'girl' => 'female', default => null
                    },
                    'date_of_birth' => $this->date($row['date_of_birth'] ?? null),
                    'class_id' => $class->id,
                    'section_id' => $section->id,
                    'roll_no' => trim($row['roll_no'] ?? '') ?: null,
                    'admission_date' => $this->date($row['admission_date'] ?? null) ?? now()->toDateString(),
                    'status' => 'active',
                ] + Arr::map(Arr::only($row, ['father_name', 'father_phone', 'father_national_id', 'mother_name', 'mother_phone', 'address', 'city', 'national_id']), fn ($v) => trim((string) $v) ?: null));
            }
        });

        Activity::log('student.imported', 'Imported '.count($rows).' students from a file');

        return redirect()->route('school.students.index')->with('success', count($rows).' students imported.');
    }

    public function promoteForm(Request $request): View
    {
        $from = Section::find($request->integer('from'));

        return view('school.students.promote', [
            'groups' => Section::grouped(),
            'from' => $from,
            'students' => $from ? $from->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get() : collect(),
            'suggested' => $from ? $this->nextSection($from) : null,
        ]);
    }

    public function promote(Request $request): RedirectResponse
    {
        $schoolId = current_school()->id;
        $data = $request->validate([
            'from' => ['required', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'action' => ['required', Rule::in(['move', 'graduate'])],
            'to' => ['required_if:action,move', 'nullable', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['integer'],
        ], ['students.required' => 'Tick at least one student.', 'to.required_if' => 'Choose the class to move them to.']);

        $students = Student::query()->active()->where('section_id', $data['from'])->whereIn('id', $data['students'])->get();
        $to = $data['action'] === 'move' ? Section::with('schoolClass')->find($data['to']) : null;

        DB::transaction(function () use ($students, $to) {
            foreach ($students as $student) {
                $student->update($to
                    ? ['class_id' => $to->class_id, 'section_id' => $to->id]
                    : ['status' => 'graduated', 'left_on' => now()->toDateString(), 'left_reason' => 'Passed out']);
            }
        });

        $what = $to ? 'moved to '.$to->label() : 'marked as passed out';
        Activity::log('student.promoted', $students->count()." students {$what}");

        return redirect()->route('school.students.promote', ['from' => $data['from']])->with('success', $students->count()." students {$what}.");
    }

    /** Same section name in the next class up, if there is one. */
    protected function nextSection(Section $from): ?Section
    {
        $class = $from->schoolClass;
        $next = SchoolClass::query()->ordered()->get()->skipUntil(fn ($c) => $c->id === $class?->id)->skip(1)->first();

        return $next ? ($next->sections()->where('name', $from->name)->first() ?? $next->sections()->first()) : null;
    }

    /** @return list<array<string, string>> */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle) ?: [];
        // Excel adds an invisible "BOM" at the start; strip it and normalise the names.
        $header = array_map(fn ($h) => Str::snake(Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)))), $header);

        if (! in_array('name', $header, true)) {
            fclose($handle);

            return [];
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), ''));
        }
        fclose($handle);

        return $rows;
    }

    /** Accepts 2016-04-12, 12/04/2016 and 12-04-2016 (day first). */
    protected function date(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // try the next format
            }
        }

        return null;
    }
}
