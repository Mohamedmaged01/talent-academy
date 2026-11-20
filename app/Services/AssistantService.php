<?php

namespace App\Services;

use App\Models\AdmissionRequest;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Talent AI": an assistant backed by Google Gemini (as in the original
 * prototype). The API key stays on the server. Only aggregate figures are sent
 * as context: no names, phone numbers or other personal details.
 */
class AssistantService
{
    public function configured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  list<array{role: string, text: string}>  $history
     */
    public function ask(User $user, array $history, string $message): string
    {
        if (! $this->configured()) {
            throw new RuntimeException(__('The assistant is not configured. Add GEMINI_API_KEY to the .env file.'));
        }

        $contents = [];
        foreach (array_slice($history, -10) as $turn) {
            $contents[] = ['role' => $turn['role'] === 'model' ? 'model' : 'user', 'parts' => [['text' => $turn['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $response = Http::timeout(45)
            ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'systemInstruction' => ['parts' => [['text' => $this->systemPrompt($user)]]],
                'contents' => $contents,
                'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1200],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(__('The AI service returned an error (HTTP :status). Try again in a moment.', ['status' => $response->status()]));
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode("\n");

        if ($text === '') {
            throw new RuntimeException(__('The AI service returned an empty answer. Try rephrasing your question.'));
        }

        return trim($text);
    }

    private function systemPrompt(User $user): string
    {
        $language = app()->getLocale() === 'ar' ? 'Arabic' : 'English';

        return implode("\n", [
            'You are "Talent AI", the assistant inside the Talent Academy platform (an educational and training academy in Beni Mazar, Minya, Egypt).',
            'The academy runs two tracks: academic education (American Diploma, high school, foundation) and professional training (AI, cyber security, software, languages, administration).',
            "Answer in {$language} unless the user writes in another language. Be concise and practical; use short lists where helpful.",
            'Use the snapshot below for questions about the academy. If the snapshot does not contain the answer, say so and suggest which page of the platform to check. Never invent numbers.',
            'Rules the platform enforces: certificates need ≥ 80% attendance and ≥ 70% exam average; payments can never exceed an invoice balance; records with history are archived, not deleted.',
            '',
            'Snapshot (generated '.now()->toDateTimeString().'):',
            json_encode($this->snapshot($user), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

    /** Aggregate figures only. */
    public function snapshot(User $user): array
    {
        if ($user->role === 'teacher') {
            $batches = Batch::where('teacher_id', $user->id)->whereIn('status', ['active', 'upcoming'])->with('course')->get();

            return [
                'role' => 'teacher',
                'classes' => $batches->map(fn (Batch $b) => [
                    'class' => $b->name,
                    'course' => $b->course->title_en,
                    'students' => $b->activeStudents()->count(),
                    'attendance_rate_30d' => $this->attendanceRate([$b->id]),
                    'upcoming_exams' => $b->exams()->where('exam_date', '>=', today())->count(),
                    'assignments_to_grade' => AssignmentSubmission::whereNull('graded_at')->whereHas('assignment', fn ($q) => $q->where('batch_id', $b->id))->count(),
                ])->values()->all(),
            ];
        }

        $open = Invoice::open()->get();

        return [
            'role' => 'administrator',
            'students' => Student::selectRaw('pathway, status, count(*) as total')->groupBy('pathway', 'status')->get()
                ->map(fn ($r) => ['pathway' => $r->pathway, 'status' => $r->status, 'count' => (int) $r->total])->all(),
            'active_classes' => Batch::where('status', 'active')->count(),
            'teachers' => User::where('role', 'teacher')->where('is_active', true)->count(),
            'attendance_rate_30d' => $this->attendanceRate(),
            'pending_applications' => AdmissionRequest::where('status', 'pending')->count(),
            'upcoming_exams_14d' => Exam::whereBetween('exam_date', [today(), today()->addDays(14)])->count(),
            'open_support_tickets' => SupportTicket::open()->count(),
            'finance_egp' => [
                'collected_this_month' => round((float) Payment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'), 2),
                'expenses_this_month' => round((float) Expense::where('spent_at', '>=', now()->startOfMonth()->toDateString())->sum('amount'), 2),
                'outstanding_total' => round($open->sum(fn (Invoice $i) => $i->balance()), 2),
                'overdue_total' => round($open->filter(fn (Invoice $i) => $i->due_date->isPast())->sum(fn (Invoice $i) => $i->balance()), 2),
                'overdue_invoices' => $open->filter(fn (Invoice $i) => $i->due_date->isPast())->count(),
            ],
        ];
    }

    private function attendanceRate(?array $batchIds = null): ?float
    {
        $query = AttendanceRecord::where('date', '>=', now()->subDays(30)->toDateString())
            ->when($batchIds !== null, fn ($q) => $q->whereIn('batch_id', $batchIds));
        $total = (clone $query)->count();

        return $total ? round((clone $query)->whereIn('status', ['present', 'late'])->count() / $total * 100, 1) : null;
    }
}
