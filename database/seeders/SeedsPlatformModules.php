<?php

namespace Database\Seeders;

use App\Http\Controllers\MessageController;
use App\Models\AdminRole;
use App\Models\Certificate;
use App\Models\CertificateLookup;
use App\Models\CertificateNomination;
use App\Models\Course;
use App\Models\Download;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Expense;
use App\Models\Lecture;
use App\Models\LectureView;
use App\Models\LibraryItem;
use App\Models\LoginEvent;
use App\Models\MediaItem;
use App\Models\MessageTemplate;
use App\Models\PayrollRecord;
use App\Models\PermissionDelegation;
use App\Models\Question;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Webhook;
use App\Services\CertificateService;
use App\Services\LedgerService;
use App\Support\Notify;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for learning content, communication, teacher tools, payroll and
 * the ledger, roles and the security log. Used by DatabaseSeeder.
 */
trait SeedsPlatformModules
{
    private function seedPlatformModules(Collection $branches, Collection $teachers, Collection $courses, Collection $batches, Collection $students): void
    {
        $this->learningContent($courses, $batches);
        $this->libraryAndDownloads($courses);
        $this->conversations($batches, $students, $teachers);
        $this->teacherTools($batches, $students);
        $this->payroll($teachers);
        $this->openingBalance($branches);
        $this->rolesAndSecurity($branches);
    }

    private function learningContent(Collection $courses, Collection $batches): void
    {
        $videos = [
            'MATH-AD-102' => [['المحاضرة 1: جوهر التفاضل والتكامل', 'https://www.youtube.com/watch?v=WUvTyaaNkzM', 17], ['المحاضرة 2: المتجهات ومعناها الهندسي', 'https://www.youtube.com/watch?v=fNk_zzaMoSs', 10]],
            'AI-PRO-201' => [['Lecture 1 — What is a neural network?', 'https://www.youtube.com/watch?v=aircAruvnKk', 19], ['Lecture 2 — Gradient descent: how networks learn', 'https://www.youtube.com/watch?v=IHZwWFHWa-w', 21]],
            'WEB-PRO-203' => [['Lecture 1 — HTML crash course', 'https://www.youtube.com/watch?v=UB1O30fR-EE', 60]],
            '*' => [['Recorded session — Python fundamentals', 'https://www.youtube.com/watch?v=rfscVS0vtbw', 270]],
        ];

        foreach ($batches->where('status', 'active') as $batch) {
            if (str_starts_with($batch->course->pathway, 'training') || $batch->code === 'B-ENG-12A') {
                $batch->update(['meeting_url' => 'https://meet.google.com/'.strtolower(Str::random(3).'-'.Str::random(4).'-'.Str::random(3))]);
            }

            $list = $videos[$batch->course->code] ?? $videos['*'];
            $students = $batch->activeStudents()->get();

            foreach ($list as $i => [$title, $url, $minutes]) {
                $lecture = Lecture::create([
                    'batch_id' => $batch->id, 'teacher_id' => $batch->teacher_id, 'title' => $title, 'video_url' => $url,
                    'duration_minutes' => $minutes, 'sort_order' => $i + 1, 'is_published' => true,
                    'description' => $i === 0 ? 'شاهد المحاضرة قبل الحصة القادمة وسجّل أسئلتك.' : null,
                    'created_at' => now()->subDays(14 - $i * 5),
                ]);

                foreach ($students->take((int) ceil($students->count() * ($i === 0 ? 0.7 : 0.4))) as $student) {
                    LectureView::create(['lecture_id' => $lecture->id, 'student_id' => $student->id, 'views' => 1 + $student->id % 3, 'last_viewed_at' => now()->subDays(mt_rand(1, 6))]);
                }
            }
        }

        // Question bank per course: [type, body, options, answer, points]
        $bank = [
            'MATH-AD-102' => [
                ['mcq', 'ما مشتقة الدالة f(x) = x² ؟', ['2x', 'x', 'x²', '2'], '2x', 2],
                ['mcq', 'قيمة sin(90°) تساوي:', ['0', '1', '-1', '0.5'], '1', 1],
                ['true_false', 'مجموع زوايا المثلث يساوي 180 درجة.', null, 'true', 1],
                ['mcq', 'حل المعادلة 2x + 6 = 0 هو:', ['x = 3', 'x = -3', 'x = 6', 'x = -6'], 'x = -3', 2],
                ['short_answer', 'ما ناتج 7 × 8 ؟', null, '56', 1],
                ['true_false', 'التكامل هو العملية العكسية للتفاضل.', null, 'true', 1],
            ],
            'AI-PRO-201' => [
                ['mcq', 'Which algorithm is used to train neural networks by adjusting weights?', ['Gradient descent', 'Binary search', 'Bubble sort', 'Dijkstra'], 'Gradient descent', 2],
                ['true_false', 'A neural network can only have one hidden layer.', null, 'false', 1],
                ['mcq', 'Which Python library is most used for deep learning?', ['PyTorch', 'Pandas', 'Requests', 'Flask'], 'PyTorch', 1],
                ['short_answer', 'What does the abbreviation "AI" stand for?', null, 'artificial intelligence', 2],
                ['mcq', 'Splitting data into training and test sets helps detect:', ['Overfitting', 'Syntax errors', 'Slow internet', 'Low disk space'], 'Overfitting', 2],
                ['true_false', 'Prompt engineering means designing good instructions for a language model.', null, 'true', 1],
            ],
            'ENG-AD-101' => [
                ['mcq', 'Choose the correct sentence.', ['She don\'t like tea.', 'She doesn\'t like tea.', 'She not like tea.', 'She no likes tea.'], 'She doesn\'t like tea.', 1],
                ['mcq', 'The synonym of "rapid" is:', ['slow', 'quick', 'late', 'quiet'], 'quick', 1],
                ['true_false', '"Their", "there" and "they\'re" have the same meaning.', null, 'false', 1],
                ['short_answer', 'Write the past tense of "go".', null, 'went', 1],
            ],
            'WEB-PRO-203' => [
                ['mcq', 'Which HTML tag creates a link?', ['<a>', '<link>', '<href>', '<url>'], '<a>', 1],
                ['mcq', 'Which language styles web pages?', ['CSS', 'SQL', 'PHP', 'Python'], 'CSS', 1],
                ['true_false', 'JavaScript runs only on the server.', null, 'false', 1],
                ['short_answer', 'Which HTTP method is normally used to submit a form that changes data?', null, 'post', 2],
            ],
        ];

        foreach ($bank as $code => $questions) {
            $course = $courses[$code] ?? null;
            if (! $course) {
                continue;
            }
            $author = $batches->firstWhere('course_id', $course->id)?->teacher_id;
            foreach ($questions as [$type, $body, $options, $answer, $points]) {
                Question::create([
                    'course_id' => $course->id, 'created_by' => $author, 'type' => $type, 'body' => $body,
                    'options' => $options, 'correct_answer' => $answer, 'points' => $points, 'difficulty' => ['easy', 'medium', 'hard'][$points % 3],
                ]);
            }
        }

        // For each active class with a question bank: a finished online quiz (with attempts) and one open today.
        foreach ($batches->where('status', 'active') as $batch) {
            $questions = Question::where('course_id', $batch->course_id)->get();
            if ($questions->isEmpty()) {
                continue;
            }

            $past = Exam::create([
                'batch_id' => $batch->id, 'title' => 'اختبار إلكتروني قصير — مراجعة الوحدة الأولى', 'type' => 'quiz', 'mode' => 'online',
                'instructions' => 'أجب عن جميع الأسئلة. لديك 20 دقيقة من لحظة البدء.',
                'exam_date' => now()->subDays(9)->toDateString(), 'starts_at' => '09:00', 'duration_minutes' => 20,
                'closes_at' => now()->subDays(7)->setTime(23, 0), 'max_score' => 10, 'pass_score' => 5, 'results_published' => true,
            ]);
            $open = Exam::create([
                'batch_id' => $batch->id, 'title' => 'Online quiz — this week\'s review', 'type' => 'quiz', 'mode' => 'online',
                'instructions' => 'Answer every question. The timer starts when you press Start and cannot be paused.',
                'exam_date' => now()->toDateString(), 'starts_at' => '00:00', 'duration_minutes' => 20,
                'closes_at' => now()->addDays(3)->setTime(23, 0), 'max_score' => 10, 'pass_score' => 5, 'results_published' => true,
            ]);

            foreach ([$past, $open] as $exam) {
                foreach ($questions->values() as $i => $q) {
                    $exam->questions()->attach($q->id, ['sort_order' => $i + 1]);
                }
                $exam->syncScoreFromQuestions();
            }

            foreach ($batch->activeStudents()->get() as $n => $student) {
                $answers = [];
                $score = 0;
                foreach ($questions as $k => $q) {
                    $right = ($student->id + $k) % 4 !== 0; // about three answers in four are right
                    $given = $right ? $q->correct_answer : match ($q->type) {
                        'true_false' => $q->correct_answer === 'true' ? 'false' : 'true',
                        'mcq' => collect($q->choices())->first(fn ($c) => $c !== $q->correct_answer),
                        default => 'لا أعرف',
                    };
                    $answers[$q->id] = $given;
                    $score += $q->isCorrect($given) ? $q->points : 0;
                }
                $started = $past->opensAt()->copy()->addHours(2 + $n % 5);
                ExamAttempt::create(['exam_id' => $past->id, 'student_id' => $student->id, 'started_at' => $started, 'submitted_at' => $started->copy()->addMinutes(8 + $n % 10), 'answers' => $answers, 'score' => $score, 'status' => 'submitted']);
                ExamResult::create(['exam_id' => $past->id, 'student_id' => $student->id, 'score' => $score, 'remarks' => 'تصحيح آلي']);
            }
        }
    }

    private function libraryAndDownloads(Collection $courses): void
    {
        foreach ([
            ['Calculus Volume 1', 'OpenStax', 'book', 'academic', 'MATH-AD-102', 'https://openstax.org/details/books/calculus-volume-1', 'كتاب مفتوح المصدر يغطي النهايات والاشتقاق والتكامل، مناسب لطلاب الدبلومة الأمريكية.', 873],
            ['College Physics 2e', 'OpenStax', 'book', 'academic', 'PHYS-AD-103', 'https://openstax.org/details/books/college-physics-2e', 'مرجع شامل للفيزياء العامة مع أمثلة محلولة.', 1272],
            ['Digital SAT — official practice', 'College Board', 'reference', 'academic', 'ENG-AD-101', 'https://satsuite.collegeboard.org/digital/digital-practice-preparation', 'اختبارات تدريبية رسمية للـ SAT الرقمي.', null],
            ['The Python Tutorial', 'Python Software Foundation', 'reference', 'training', 'AI-PRO-201', 'https://docs.python.org/3/tutorial/', 'الدليل الرسمي لتعلم بايثون من الصفر.', null],
            ['OWASP Top 10 — web security risks', 'OWASP Foundation', 'training_kit', 'training', 'CYB-PRO-202', 'https://owasp.org/www-project-top-ten/', 'الحقيبة المرجعية لأهم ثغرات تطبيقات الويب وطرق الوقاية منها.', null],
            ['MDN Web Docs — Learn web development', 'Mozilla', 'training_kit', 'training', 'WEB-PRO-203', 'https://developer.mozilla.org/en-US/docs/Learn', 'مسار تعليمي كامل لـ HTML و CSS و JavaScript.', null],
            ['Neural networks — video series', '3Blue1Brown', 'video', 'training', 'AI-PRO-201', 'https://www.youtube.com/playlist?list=PLZHQObOWTQDNU6R1_67000Dx_ZCJB-3pi', 'سلسلة مرئية تشرح الشبكات العصبية بصرياً.', null],
        ] as $i => [$title, $author, $type, $pathway, $code, $url, $description, $pages]) {
            LibraryItem::create([
                'title' => $title, 'author' => $author, 'type' => $type, 'pathway' => $pathway, 'course_id' => $courses[$code]?->id,
                'url' => $url, 'description' => $description, 'pages' => $pages, 'downloads_count' => 40 - $i * 4,
                'is_published' => true, 'uploaded_by' => $this->admin->id,
            ]);
        }

        foreach ([
            ['استمارة طلب الالتحاق', 'Enrollment application form', 'forms', 'public', 'Talent Academy - Enrollment Application Form'],
            ['دليل برامج التدريب المهني', 'Professional training programs guide', 'brochures', 'public', 'Talent Academy - Training Programs Guide'],
            ['جدول امتحانات نهاية الفصل', 'End-of-term exam timetable', 'schedules', 'members', 'Talent Academy - End of Term Exam Timetable'],
            ['دليل استخدام بوابة الطالب', 'Student portal user guide', 'guides', 'students', 'Talent Academy - Student Portal Guide'],
            ['نموذج تقرير متابعة الطالب', 'Student progress report template', 'forms', 'teachers', 'Talent Academy - Student Progress Report Template'],
            ['دليل ولي الأمر', 'Parent handbook', 'guides', 'parents', 'Talent Academy - Parent Handbook'],
        ] as $i => [$ar, $en, $category, $audience, $pdfTitle]) {
            $path = 'downloads/demo-'.Str::slug($en).'.pdf';
            $pdf = $this->simplePdf($pdfTitle);
            Storage::disk('local')->put($path, $pdf);

            Download::create([
                'title_ar' => $ar, 'title_en' => $en, 'category' => $category, 'audience' => $audience,
                'file_path' => $path, 'original_name' => Str::slug($en).'.pdf', 'size_bytes' => strlen($pdf),
                'downloads_count' => 25 - $i * 3, 'is_published' => true, 'uploaded_by' => $this->admin->id,
            ]);
        }
    }

    /** A valid one-page PDF with a single line of text (ASCII), for demo downloads. */
    private function simplePdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 18 Tf 60 760 Td ({$text}) Tj ET\nBT /F1 11 Tf 60 730 Td (Demo document generated by the seeder.) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function conversations(Collection $batches, Collection $students, Collection $teachers): void
    {
        $teacher = User::where('email', 'teacher@talent.test')->first();
        $parent = User::where('email', 'parent@talent.test')->first();
        $studentUser = User::where('email', 'student@talent.test')->first();
        $aiTrainer = $teachers[3];

        $thread = MessageController::startThread($parent, [$teacher], 'متابعة مستوى مصطفى في الرياضيات', 'السلام عليكم أستاذ طارق، أود الاطمئنان على مستوى مصطفى في الرياضيات وهل يحتاج إلى حصص تقوية قبل الامتحانات؟');
        $thread->post($teacher, 'وعليكم السلام، مصطفى ملتزم ومستواه جيد جداً. أنصح بحل تمارين الوحدة الثانية في بنك الأسئلة ومراجعة المحاضرة المسجلة الأخيرة.');
        $thread->messages()->latest('id')->first()->update(['created_at' => now()->subDays(2)]);

        $thread = MessageController::startThread($studentUser, [$aiTrainer], 'Question about the neural network assignment', 'Hello, should we use PyTorch or TensorFlow for the assignment, or is either fine?');
        $thread->post($aiTrainer, 'Either is fine. PyTorch is what we use in class, so the examples will be easier to follow.');

        foreach ($teachers as $member) {
            MessageController::startThread($this->admin, [$member], 'اجتماع هيئة التدريس يوم الخميس', "السادة أعضاء هيئة التدريس،\nيُعقد اجتماع شهري يوم الخميس القادم الساعة 3 عصراً بقاعة 101 لمناقشة نتائج الاختبارات ونسب الحضور.\nمع التحية.");
        }

        MessageTemplate::create(['name' => 'تذكير بالرسوم', 'subject' => 'تذكير بموعد سداد القسط', 'body' => "عزيزي ولي الأمر،\nنذكركم بموعد سداد القسط المستحق. يمكنكم السداد بمقر الأكاديمية أو عبر فودافون كاش.\nشكراً لتعاونكم.", 'created_by' => $this->admin->id]);
        MessageTemplate::create(['name' => 'Exam reminder', 'subject' => 'Upcoming exam reminder', 'body' => "Dear students,\nThis is a reminder that your exam is next week. Review the recorded lectures and the question bank.\nGood luck!", 'created_by' => $this->admin->id]);

        foreach ([
            [$teacher, 'جهاز العرض في قاعة 205 لا يعمل', 'technical', 'high', 'resolved', 'جهاز البروجكتور لا يتصل بالحاسب منذ يوم السبت.', 'تم استبدال كابل HDMI والجهاز يعمل الآن. شكراً للإبلاغ.'],
            [$parent, 'استفسار عن تقسيط رسوم البرنامج', 'financial', 'normal', 'in_progress', 'هل يمكن تقسيم القسط الثالث على دفعتين؟', 'تم تحويل طلبكم للإدارة المالية وسنتواصل معكم خلال يومين.'],
            [$studentUser, 'لا أستطيع تحميل الحقيبة التدريبية', 'technical', 'normal', 'open', 'عند الضغط على تحميل يظهر خطأ في المتصفح.', null],
        ] as $i => [$user, $subject, $category, $priority, $status, $body, $reply]) {
            $ticket = SupportTicket::create([
                'number' => SupportTicket::nextNumber(), 'user_id' => $user->id, 'subject' => $subject, 'category' => $category,
                'priority' => $priority, 'status' => $status, 'body' => $body, 'last_reply_at' => now()->subDays(3 - $i),
                'closed_at' => $status === 'resolved' ? now()->subDays(2) : null, 'created_at' => now()->subDays(4 - $i),
            ]);
            if ($reply) {
                $ticket->replies()->create(['user_id' => $this->admin->id, 'body' => $reply, 'created_at' => now()->subDays(3 - $i)]);
            }
        }

        Notify::users($studentUser, 'New recorded lecture in :class: :title', ['class' => 'Grade 10A', 'title' => 'المحاضرة 2: المتجهات ومعناها الهندسي'], route('student.lectures'), 'video');
        Notify::users($studentUser, 'New assignment ":title" due :date', ['title' => 'Linear equations worksheet', 'date' => now()->addDays(3)->format('Y-m-d')], route('student.assignments.index'), 'notebook-pen');
        Notify::users($teacher, ':name submitted ":title"', ['name' => 'مصطفى عمرو محمود', 'title' => 'Linear equations worksheet'], route('teacher.assignments.index'), 'inbox');
        Notify::users($parent, 'Payment received: :amount EGP (receipt :receipt)', ['amount' => '1,500.00', 'receipt' => 'RCP-DEMO'], route('parent.dashboard'), 'wallet');
    }

    private function teacherTools(Collection $batches, Collection $students): void
    {
        $teacher = User::where('email', 'teacher@talent.test')->first();
        $math = $batches->firstWhere('code', 'B-MATH-10A');
        $demo = $students[0];

        $demo->fileNotes()->create(['author_id' => $teacher->id, 'batch_id' => $math->id, 'visible_to_parent' => true, 'body' => 'مستوى ممتاز في حل المعادلات. يُنصح بالتركيز على مسائل الهندسة الفراغية قبل امتحان منتصف الفصل.', 'created_at' => now()->subDays(6)]);
        $demo->fileNotes()->create(['author_id' => $teacher->id, 'batch_id' => $math->id, 'visible_to_parent' => false, 'body' => 'يتأخر أحياناً في تسليم الواجبات؛ متابعة خلال الأسبوعين القادمين.', 'created_at' => now()->subDays(2)]);

        // Nominate the eligible students of one active class so the approval queue has work in it.
        $service = app(CertificateService::class);
        foreach ($batches->where('status', 'active') as $batch) {
            $eligible = $service->eligibility($batch)->filter(fn ($r) => $r['eligible'] && ! $r['certificate'])->take(3);
            foreach ($eligible as $row) {
                CertificateNomination::create(['batch_id' => $batch->id, 'student_id' => $row['student']->id, 'nominated_by' => $batch->teacher_id, 'status' => 'pending', 'note' => 'أتمّ متطلبات البرنامج بتميّز.']);
            }
            if ($eligible->isNotEmpty()) {
                break;
            }
        }
    }

    private function payroll(Collection $teachers): void
    {
        // Last month paid, this month waiting to be paid.
        foreach ([1, 0] as $monthsAgo) {
            $period = now()->subMonths($monthsAgo)->format('Y-m');

            foreach ($teachers as $i => $teacher) {
                $record = PayrollRecord::create([
                    'user_id' => $teacher->id, 'branch_id' => $teacher->branch_id, 'period' => $period,
                    'basic_salary' => $teacher->teacherProfile->monthly_salary,
                    'allowances' => 500, 'bonus' => $i % 3 === 0 ? 750 : 0, 'deductions' => $i % 4 === 1 ? 300 : 0,
                    'status' => 'draft',
                ]);

                if ($monthsAgo > 0) {
                    $paidAt = now()->subMonths($monthsAgo)->endOfMonth()->subDays(2)->setTime(12, 0);
                    $expense = Expense::create([
                        'branch_id' => $record->branch_id, 'category' => 'salaries', 'description' => "Payroll {$period} — {$teacher->name}",
                        'amount' => $record->net(), 'spent_at' => $paidAt->toDateString(), 'recorded_by' => $this->admin->id,
                    ]);
                    $record->update(['status' => 'paid', 'method' => $i % 2 ? 'bank_transfer' : 'cash', 'paid_at' => $paidAt, 'paid_by' => $this->admin->id, 'expense_id' => $expense->id]);
                }
            }
        }
    }

    private function openingBalance(Collection $branches): void
    {
        app(LedgerService::class)->post(now()->subMonths(6)->startOfMonth(), 'Opening balance — owner capital deposited in the bank', [
            ['account' => LedgerService::BANK, 'debit' => 250000, 'memo' => 'Capital deposit'],
            ['account' => '3000', 'credit' => 250000],
        ], null, $branches['BMZ-01']->id, 'manual', $this->admin->id);
    }

    private function rolesAndSecurity(Collection $branches): void
    {
        $grant = fn (array $manage, array $view = []) => Permissions::clean(array_merge(
            array_map(fn ($m) => "{$m}.manage", $manage), array_map(fn ($m) => "{$m}.view", array_merge($manage, $view)),
        ));

        $accountant = AdminRole::create([
            'name_ar' => 'محاسب', 'name_en' => 'Accountant', 'description' => 'Invoices, payments, expenses, payroll and the ledger.',
            'permissions' => $grant(['finance', 'payroll', 'ledger'], ['students', 'reports']),
        ]);
        $registrar = AdminRole::create([
            'name_ar' => 'مسؤول القبول والتسجيل', 'name_en' => 'Registrar', 'description' => 'Applications, students, parents, classes and certificates.',
            'permissions' => $grant(['students', 'parents', 'admissions', 'academics', 'communication'], ['teachers', 'certificates', 'exams', 'reports']),
        ]);
        $branchManager = AdminRole::create([
            'name_ar' => 'مدير فرع', 'name_en' => 'Branch manager', 'description' => 'Runs one branch: its students, classes and money.',
            'permissions' => $grant(['students', 'parents', 'admissions', 'academics', 'finance', 'communication', 'support'], ['teachers', 'exams', 'certificates', 'reports', 'library']),
            'branch_scoped' => true,
        ]);

        $users = [
            ['accountant@talent.test', 'هالة سمير', $accountant, null],
            ['registrar@talent.test', 'محمود جابر', $registrar, null],
            ['maghagha@talent.test', 'أ. محمد فوزي', $branchManager, $branches['MAG-02']->id],
        ];
        foreach ($users as [$email, $name, $role, $branchId]) {
            User::create(['name' => $name, 'email' => $email, 'role' => 'admin', 'admin_role_id' => $role->id, 'branch_id' => $branchId, 'password' => 'password', 'phone' => '0100'.mt_rand(1000000, 9999999)]);
        }

        $registrarUser = User::where('email', 'registrar@talent.test')->first();
        PermissionDelegation::create([
            'from_user_id' => $this->admin->id, 'to_user_id' => $registrarUser->id, 'permissions' => ['finance.view'],
            'reason' => 'متابعة سداد رسوم الطلاب الجدد خلال فترة التسجيل', 'status' => 'active',
            'starts_at' => now()->subDays(2), 'ends_at' => now()->addDays(12), 'reviewed_by' => $this->admin->id, 'reviewed_at' => now()->subDays(2),
        ]);
        PermissionDelegation::create([
            'to_user_id' => User::where('email', 'accountant@talent.test')->value('id'), 'permissions' => ['content.manage', 'content.view'],
            'reason' => 'نشر إعلان مواعيد السداد على الموقع', 'requested_days' => 5, 'status' => 'pending',
        ]);

        Webhook::create([
            'name' => 'Accounting system (example)', 'url' => 'https://example.com/webhooks/talent-academy', 'secret' => Str::random(40),
            'events' => ['payment.received', 'invoice.created'], 'is_active' => false,
        ]);

        foreach (['images/news_ai.jpg' => 'AI workshop', 'images/news_cyber.jpg' => 'Cyber security lab', 'images/news_diploma.jpg' => 'American Diploma', 'images/news_programs.jpg' => 'Programs', 'images/students-lab.jpg' => 'Students in the lab', 'images/pathway_academic.jpg' => 'Academic pathway', 'images/pathway_training.jpg' => 'Training pathway'] as $path => $title) {
            MediaItem::create(['title' => $title, 'path' => $path, 'mime' => 'image/jpeg', 'size_bytes' => file_exists(public_path($path)) ? filesize(public_path($path)) : 0, 'uploaded_by' => $this->admin->id]);
        }

        foreach (User::whereIn('email', ['admin@talent.test', 'teacher@talent.test', 'student@talent.test', 'parent@talent.test'])->get() as $i => $user) {
            for ($d = 0; $d < 3; $d++) {
                LoginEvent::create(['user_id' => $user->id, 'email' => $user->email, 'event' => 'login', 'successful' => true, 'ip_address' => '197.36.'.(10 + $i).'.'.(20 + $d), 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0', 'created_at' => now()->subDays($d)->subHours($i * 2)]);
            }
        }
        foreach (range(1, 6) as $n) {
            LoginEvent::create(['email' => 'admin@talent.test', 'event' => 'failed', 'successful' => false, 'ip_address' => '41.65.200.17', 'user_agent' => 'python-requests/2.31', 'created_at' => now()->subHours(5)->addMinutes($n)]);
        }

        $certificate = Certificate::first();
        foreach ([[$certificate?->serial, $certificate?->id, 'valid'], ['TA-CERT-2025-FAKE01', null, 'not_found'], [$certificate?->serial, $certificate?->id, 'valid']] as $i => [$serial, $id, $result]) {
            if ($serial) {
                CertificateLookup::create(['serial' => $serial, 'certificate_id' => $id, 'result' => $result, 'ip_address' => '156.204.33.'.(10 + $i), 'created_at' => now()->subDays($i * 3)]);
            }
        }
    }
}
