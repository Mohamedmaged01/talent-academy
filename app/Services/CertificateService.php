<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Support\Collection;

class CertificateService
{
    /** Graduation thresholds carried over from the original platform's rules. */
    public const MIN_ATTENDANCE = 80;

    public const MIN_AVERAGE = 70;

    /**
     * Eligibility rows for every active student in a batch.
     *
     * @return Collection<int, array{student: Student, attendance: ?float, average: ?float, eligible: bool, certificate: ?Certificate}>
     */
    public function eligibility(Batch $batch): Collection
    {
        $existing = Certificate::where('batch_id', $batch->id)->where('status', 'valid')->get()->keyBy('student_id');

        return $batch->activeStudents()->orderBy('full_name')->get()->map(function (Student $student) use ($batch, $existing) {
            $attendance = $student->attendanceRate($batch->id);
            $average = $student->averageExamPercent($batch->id);

            return [
                'student' => $student,
                'attendance' => $attendance,
                'average' => $average,
                'eligible' => $attendance !== null && $average !== null
                    && $attendance >= self::MIN_ATTENDANCE && $average >= self::MIN_AVERAGE,
                'certificate' => $existing->get($student->id),
            ];
        });
    }

    public function issue(Student $student, ?Batch $batch, string $titleAr, string $titleEn, ?string $grade = null): Certificate
    {
        $certificate = Certificate::create([
            'serial' => Certificate::newSerial(),
            'student_id' => $student->id,
            'batch_id' => $batch?->id,
            'course_id' => $batch?->course_id,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'grade' => $grade,
            'issued_at' => now(),
            'status' => 'valid',
            'issued_by' => auth()->id(),
        ]);

        ActivityLog::record('certificate.issued', "Certificate {$certificate->serial} issued to {$student->full_name}", $certificate);

        return $certificate;
    }

    public static function gradeFor(?float $average): ?string
    {
        return match (true) {
            $average === null => null,
            $average >= 90 => 'Excellent',
            $average >= 80 => 'Very Good',
            $average >= 70 => 'Good',
            default => 'Pass',
        };
    }
}
