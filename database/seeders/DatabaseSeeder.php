<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AdmissionRequest;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Expense;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\NewsArticle;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Demo data for Talent Academy. All dates are relative to "now" so the
 * dashboards always look alive. Every demo account uses the password "password".
 */
class DatabaseSeeder extends Seeder
{
    use SeedsPlatformModules;

    private User $admin;

    public function run(): void
    {
        mt_srand(2026);

        Setting::put(Setting::DEFAULTS);

        $branches = $this->branches();
        $this->admin = User::create([
            'name' => 'أحمد سامي', 'email' => 'admin@talent.test', 'phone' => '01011699453',
            'role' => 'admin', 'branch_id' => null, 'password' => 'password',
        ]);

        $teachers = $this->teachers($branches['BMZ-01']);
        $courses = $this->courses();
        $batches = $this->batches($branches, $courses, $teachers);
        $students = $this->students($branches);
        $this->parents($students);
        $this->enroll($students, $batches);

        foreach ($batches as $batch) {
            $this->attendance($batch);
            $this->assignments($batch);
            $this->exams($batch);
            $this->materials($batch);
        }

        $this->finance($batches, $branches);
        foreach ($batches->where('status', 'completed') as $completedBatch) {
            $this->certificates($completedBatch);
        }
        $this->news();
        $this->announcements($batches);
        $this->admissions($courses, $branches);
        $this->inquiries();
        $this->seedPlatformModules($branches, $teachers, $courses, $batches, $students);
        $this->activity();
    }

    private function branches(): Collection
    {
        return collect([
            ['BMZ-01', 'فرع بني مزار (المقر الرئيسي)', 'Beni Mazar Branch (HQ)', 'بني مزار - المنيا', 'شارع كوبري أبو الليل بجوار مسجد الحجة زكية', '+20 86 3781200', 'أ.د. حسن هندي', 600, 'active'],
            ['MAG-02', 'فرع مغاغة', 'Maghagha Branch', 'مغاغة - المنيا', 'شارع السلام المتفرع من شارع المحطة، برج الفردوس', '+20 86 3601400', 'أ. محمد فوزي', 300, 'active'],
            ['MLW-03', 'فرع ملوي', 'Mallawi Branch', 'ملوي - المنيا', 'شارع الجيش أمام مدرسة ملوي الثانوية', '+20 86 6451800', 'أ. هشام رفعت', 250, 'inactive'],
        ])->mapWithKeys(fn ($b) => [$b[0] => Branch::create([
            'code' => $b[0], 'name_ar' => $b[1], 'name_en' => $b[2], 'city' => $b[3], 'address' => $b[4],
            'phone' => $b[5], 'manager_name' => $b[6], 'capacity' => $b[7], 'status' => $b[8],
            'email' => strtolower(substr($b[0], 0, 3)).'@talent-academy.edu.eg',
        ])]);
    }

    private function teachers(Branch $hq): Collection
    {
        $rows = [
            ['teacher@talent.test', 'أ. طارق عبد العال', 'academic_teacher', 'معلم أول رياضيات', 'Senior Mathematics Teacher', 'الرياضيات المتقدمة وحساب المثلثات', 'Advanced Mathematics & Trigonometry', 9500],
            ['hany@talent.test', 'د. هاني ممدوح', 'academic_teacher', 'محاضر الفيزياء', 'Physics Lecturer', 'الفيزياء التجريبية والكهرومغناطيسية', 'Experimental Physics & Electromagnetism', 9000],
            ['sameh@talent.test', 'د. سامح عبد الرازق', 'both', 'منسق اللغة الإنجليزية', 'English Coordinator', 'اللغة الإنجليزية و SAT', 'English Language & SAT', 10500],
            ['sherif@talent.test', 'م. أحمد الشريف', 'professional_trainer', 'مدرب الذكاء الاصطناعي', 'AI Trainer', 'الذكاء الاصطناعي وهندسة الأوامر', 'Applied AI & Prompt Engineering', 11000],
            ['othman@talent.test', 'د. عثمان عرفات حسن', 'professional_trainer', 'مدرب الأمن السيبراني', 'Cyber Security Trainer', 'أمن الشبكات واختبار الاختراق', 'Network Security & Pen-Testing', 11000],
            ['hossam@talent.test', 'م. حسام بدر', 'professional_trainer', 'مدرب تطوير الويب', 'Web Development Trainer', 'تطوير تطبيقات الويب المتكاملة', 'Full-Stack Web Development', 10000],
        ];

        return collect($rows)->map(function ($r, $i) use ($hq) {
            $user = User::create([
                'name' => $r[1], 'email' => $r[0], 'phone' => '0100'.str_pad((string) (1234500 + $i * 77), 7, '0', STR_PAD_LEFT),
                'role' => 'teacher', 'branch_id' => $hq->id, 'password' => 'password',
            ]);
            $user->teacherProfile()->create([
                'instructor_type' => $r[2], 'title_ar' => $r[3], 'title_en' => $r[4],
                'specialization_ar' => $r[5], 'specialization_en' => $r[6], 'monthly_salary' => $r[7],
                'hire_date' => now()->subYears(2 + $i)->subMonths($i),
                'office_hours' => 'الأحد والثلاثاء: 01:00 م - 03:00 م',
                'bio' => 'خبرة تزيد عن '.(6 + $i * 2).' سنوات في التدريس والتدريب.',
            ]);

            return $user;
        });
    }

    private function courses(): Collection
    {
        $rows = [
            ['ENG-AD-101', 'academic', 'اللغة الإنجليزية', 'English', 'اللغة الإنجليزية المتقدمة والقراءة النقدية', 'Advanced English & Critical Reading', 'تطوير مهارات القراءة النقدية والتحليل البلاغي والكتابة الأكاديمية لطلاب Grade 10-12 لتأهيلهم لاختبارات EST و SAT.', 'Critical reading, rhetorical analysis and academic essay writing for Grades 10-12 with EST/SAT preparation.', 'الدبلومة الأمريكية - Grade 11/12', 'American Diploma - Grade 11/12', null, 16, 4800, 'images/news_diploma.jpg'],
            ['MATH-AD-102', 'academic', 'الرياضيات', 'Mathematics', 'تأسيس الرياضيات والتفاضل والتكامل', 'Mathematics & Calculus Foundation', 'إتقان الجبر المتقدم وحساب المثلثات والتفاضل والتكامل مع بنك أسئلة تدريبي واختبارات محاكاة دورية.', 'Advanced algebra, trigonometry and calculus with a full problem bank and regular mock exams.', 'الدبلومة الأمريكية - Grade 10/11', 'American Diploma - Grade 10/11', null, 16, 4500, 'images/pathway_academic.jpg'],
            ['PHYS-AD-103', 'academic', 'العلوم', 'Science', 'الفيزياء التجريبية والمفاهيم الكهرومغناطيسية', 'Experimental Physics & Electromagnetism', 'شرح معملي تفاعلي للمفاهيم الفيزيائية داخل معامل الأكاديمية مع تطبيقات عملية ومحاكاة للامتحانات.', 'Hands-on physics in the academy labs with practical applications and past-exam practice.', 'الدبلومة الأمريكية - Grade 11/12', 'American Diploma - Grade 11/12', null, 16, 4500, 'images/students-lab.jpg'],
            ['AI-PRO-201', 'training', 'الذكاء الاصطناعي', 'Artificial Intelligence', 'دبلومة الذكاء الاصطناعي وهندسة الأوامر التطبيقية', 'Applied AI & Prompt Engineering Diploma', 'تطوير التطبيقات الذكية وربط واجهات API وبناء روبوتات الدردشة والتحليل الآلي للبيانات في بيئة تدريب عملية.', 'Build smart applications, integrate APIs, create chatbots and automate data analysis in a practical lab.', 'متوسط', 'Intermediate', 60, 8, 6500, 'images/news_ai.jpg'],
            ['CYB-PRO-202', 'training', 'الأمن السيبراني', 'Cyber Security', 'أخصائي الأمن السيبراني وحماية النظم والشبكات', 'Cyber Security Specialist & Network Defense', 'أساسيات اختبار الاختراق الأخلاقي وكشف الثغرات وأمن الخوادم والشبكات مع شهادة معتمدة لسوق العمل.', 'Ethical penetration testing, vulnerability discovery, server and network hardening with a job-ready certificate.', 'متوسط', 'Intermediate', 45, 6, 6000, 'images/news_cyber.jpg'],
            ['WEB-PRO-203', 'training', 'الحاسب الآلي', 'Computer Science', 'تطوير تطبيقات الويب الحديثة', 'Modern Full-Stack Web Development', 'بناء منصات الويب المتكاملة وقواعد البيانات مع مشاريع تخرج واقعية تؤهل للعمل الحر والشركات.', 'End-to-end web platforms and databases with real graduation projects for freelance and company work.', 'مبتدئ إلى متقدم', 'Beginner to Advanced', 80, 10, 7500, 'images/pathway_training.jpg'],
            ['LANG-PRO-204', 'training', 'اللغات', 'Languages', 'المحادثة الإنجليزية لسوق العمل', 'Business English Conversation', 'تطوير مهارات المحادثة والمقابلات الشخصية والمراسلات المهنية باللغة الإنجليزية.', 'Conversation, job interviews and professional correspondence in English.', 'جميع المستويات', 'All levels', 36, 6, 2800, 'images/news_programs.jpg'],
            ['ADM-PRO-205', 'training', 'الإدارة', 'Administration', 'المحاسبة وإدارة الأعمال الصغيرة', 'Accounting & Small Business Management', 'أساسيات المحاسبة المالية وإعداد الميزانيات وإدارة المشروعات الصغيرة باستخدام الأدوات الرقمية.', 'Financial accounting basics, budgeting and running a small business with digital tools.', 'مبتدئ', 'Beginner', 40, 6, 3200, 'images/news_programs.jpg'],
        ];

        return collect($rows)->mapWithKeys(fn ($r, $i) => [$r[0] => Course::create([
            'code' => $r[0], 'pathway' => $r[1], 'category_ar' => $r[2], 'category_en' => $r[3],
            'title_ar' => $r[4], 'title_en' => $r[5], 'description_ar' => $r[6], 'description_en' => $r[7],
            'level_ar' => $r[8], 'level_en' => $r[9], 'duration_hours' => $r[10], 'duration_weeks' => $r[11],
            'price' => $r[12], 'image_path' => $r[13], 'is_published' => true, 'sort_order' => $i,
        ])]);
    }

    private function batches(Collection $branches, Collection $courses, Collection $teachers): Collection
    {
        // [code, name, course, teacher index, branch, room, capacity, status, start offset (weeks), schedule [[day, start, end]]]
        $rows = [
            ['B-MATH-10A', 'Grade 10A — رياضيات (مجموعة 1)', 'MATH-AD-102', 0, 'BMZ-01', 'قاعة 205', 20, 'active', -8, [[6, '08:00', '10:00'], [1, '08:00', '10:00'], [3, '08:00', '10:00']]],
            ['B-PHYS-11B', 'Grade 11B — فيزياء (مجموعة 2)', 'PHYS-AD-103', 1, 'BMZ-01', 'معمل الفيزياء 2', 18, 'active', -7, [[0, '10:30', '12:30'], [2, '10:30', '12:30']]],
            ['B-ENG-12A', 'Grade 12 — English & SAT', 'ENG-AD-101', 2, 'BMZ-01', 'قاعة 101', 20, 'active', -6, [[6, '12:00', '14:00'], [2, '16:00', '18:00']]],
            ['B-AI-01', 'الذكاء الاصطناعي — الدفعة الأولى', 'AI-PRO-201', 3, 'BMZ-01', 'معمل الحاسب 1', 16, 'active', -5, [[0, '17:00', '20:00'], [3, '17:00', '20:00']]],
            ['B-CYB-01', 'الأمن السيبراني — الدفعة الأولى', 'CYB-PRO-202', 4, 'MAG-02', 'معمل الشبكات', 14, 'completed', -22, [[1, '17:00', '20:00'], [4, '17:00', '20:00']]],
            ['B-LANG-SUM', 'Summer English Bootcamp — الصيفي', 'LANG-PRO-204', 2, 'BMZ-01', 'قاعة 3', 20, 'completed', -19, [[0, '10:00', '13:00'], [2, '10:00', '13:00'], [4, '10:00', '13:00']]],
            ['B-WEB-02', 'تطوير الويب — الدفعة الثانية', 'WEB-PRO-203', 5, 'BMZ-01', 'معمل الحاسب 2', 16, 'active', -4, [[6, '17:00', '20:00'], [2, '18:00', '21:00']]],
            ['B-LANG-03', 'Business English — المسائي', 'LANG-PRO-204', 2, 'MAG-02', 'قاعة 3', 20, 'active', -3, [[1, '19:00', '21:00'], [4, '19:00', '21:00']]],
            ['B-ADM-01', 'المحاسبة — الدفعة القادمة', 'ADM-PRO-205', 0, 'BMZ-01', 'قاعة 102', 20, 'upcoming', 2, [[0, '14:00', '16:00'], [3, '14:00', '16:00']]],
        ];

        return collect($rows)->map(function ($r) use ($branches, $courses, $teachers) {
            $course = $courses[$r[2]];
            $start = now()->startOfWeek(Carbon::SATURDAY)->addWeeks($r[8]);
            $end = $start->copy()->addWeeks(max(6, $course->duration_weeks ?? 16));

            $batch = Batch::create([
                'code' => $r[0], 'name' => $r[1], 'course_id' => $course->id, 'teacher_id' => $teachers[$r[3]]->id,
                'branch_id' => $branches[$r[4]]->id, 'room' => $r[5], 'capacity' => $r[6], 'status' => $r[7],
                'start_date' => $start, 'end_date' => $end,
            ]);
            foreach ($r[9] as [$day, $from, $to]) {
                $batch->schedules()->create(['day_of_week' => $day, 'starts_at' => $from, 'ends_at' => $to]);
            }

            return $batch;
        });
    }

    private function students(Collection $branches): Collection
    {
        $male = [['محمد', 'Mohamed'], ['أحمد', 'Ahmed'], ['محمود', 'Mahmoud'], ['عمر', 'Omar'], ['يوسف', 'Youssef'], ['كريم', 'Karim'], ['عبد الرحمن', 'Abdelrahman'], ['علي', 'Ali'], ['زياد', 'Ziad'], ['مازن', 'Mazen'], ['سيف', 'Seif'], ['آدم', 'Adam'], ['إياد', 'Eyad'], ['حمزة', 'Hamza']];
        $female = [['مريم', 'Mariam'], ['سارة', 'Sara'], ['نور', 'Nour'], ['فاطمة', 'Fatma'], ['ملك', 'Malak'], ['جنى', 'Jana'], ['ياسمين', 'Yasmin'], ['آية', 'Aya'], ['منة', 'Menna'], ['رنا', 'Rana'], ['هبة', 'Heba'], ['دينا', 'Dina']];
        $fathers = [['عمرو', 'Amr'], ['سامي', 'Samy'], ['عبد الله', 'Abdallah'], ['حسين', 'Hussein'], ['فتحي', 'Fathy'], ['ربيع', 'Rabie'], ['جمال', 'Gamal'], ['صلاح', 'Salah'], ['رمضان', 'Ramadan'], ['عادل', 'Adel'], ['ناصر', 'Nasser'], ['شعبان', 'Shaaban']];
        $families = [['محمود', 'Mahmoud'], ['عبد العزيز', 'Abdelaziz'], ['الشريف', 'El-Sherif'], ['حسن', 'Hassan'], ['إبراهيم', 'Ibrahim'], ['السيد', 'El-Sayed'], ['مصطفى', 'Mostafa'], ['خليل', 'Khalil']];
        $schools = ['مدرسة بني مزار الثانوية', 'مدرسة الشهيد الرسمية للغات', 'مدرسة مغاغة الثانوية بنات', 'جامعة المنيا', 'المعهد العالي للحاسبات'];

        $students = collect();

        // The demo student account: one unified person enrolled in an academic class and a training course.
        $demoUser = User::create(['name' => 'مصطفى عمرو محمود', 'email' => 'student@talent.test', 'phone' => '01055512345', 'role' => 'student', 'branch_id' => $branches['BMZ-01']->id, 'password' => 'password']);
        $students->push(Student::create([
            'user_id' => $demoUser->id, 'branch_id' => $branches['BMZ-01']->id, 'code' => Student::nextCode(),
            'full_name' => 'مصطفى عمرو محمود', 'full_name_en' => 'Mostafa Amr Mahmoud', 'gender' => 'male',
            'birth_date' => now()->subYears(16)->subMonths(3), 'phone' => '01055512345', 'email' => 'student@talent.test',
            'pathway' => 'academic', 'grade_level' => 'Grade 10', 'school_name' => $schools[0], 'status' => 'active',
            'enrolled_at' => now()->subMonths(3), 'national_id' => '30908151800123',
        ]));

        for ($i = 1; $i < 44; $i++) {
            $isFemale = $i % 3 === 0;
            $first = $isFemale ? $female[$i % count($female)] : $male[$i % count($male)];
            $father = $fathers[($i * 7) % count($fathers)];
            $family = $families[($i * 3) % count($families)];
            $academic = $i <= 22;
            $branch = $i % 5 === 0 ? $branches['MAG-02'] : $branches['BMZ-01'];
            $grade = $academic ? 'Grade '.(10 + $i % 3) : ['طالب جامعي', 'خريج', 'Grade 12', 'موظف'][$i % 4];
            $status = $i === 40 ? 'suspended' : ($i === 41 ? 'withdrawn' : 'active');

            $student = Student::create([
                'branch_id' => $branch->id, 'code' => Student::nextCode(),
                'full_name' => "{$first[0]} {$father[0]} {$family[0]}", 'full_name_en' => "{$first[1]} {$father[1]} {$family[1]}",
                'gender' => $isFemale ? 'female' : 'male', 'birth_date' => now()->subYears($academic ? 15 + $i % 3 : 19 + $i % 6)->subDays($i * 11),
                'phone' => '01'.[0, 1, 2, 5][$i % 4].str_pad((string) (20000000 + $i * 131071 % 79999999), 8, '0', STR_PAD_LEFT),
                'pathway' => $academic ? 'academic' : 'training', 'grade_level' => $grade,
                'school_name' => $schools[$academic ? $i % 3 : 3 + $i % 2], 'status' => $status,
                'enrolled_at' => now()->subDays(20 + $i * 3),
                'created_at' => $i > 38 ? now()->subDays($i - 38) : now()->subDays(20 + $i * 3),
            ]);

            if ($i % 4 === 1) {
                $user = User::create([
                    'name' => $student->full_name, 'email' => 'student'.$i.'@talent.test', 'phone' => $student->phone,
                    'role' => 'student', 'branch_id' => $branch->id, 'password' => 'password',
                ]);
                $student->update(['user_id' => $user->id, 'email' => $user->email]);
            }

            $students->push($student);
        }

        return $students;
    }

    private function parents(Collection $students): void
    {
        $demoParent = User::create(['name' => 'عمرو محمود عبد الله', 'email' => 'parent@talent.test', 'phone' => '01011122233', 'role' => 'parent', 'password' => 'password']);
        $demoParent->children()->attach($students[0]->id, ['relationship' => 'father']);
        $demoParent->children()->attach($students[3]->id, ['relationship' => 'father']);

        foreach ($students->where('pathway', 'academic')->slice(4, 8)->values() as $i => $child) {
            $parent = User::create([
                'name' => 'ولي أمر '.explode(' ', $child->full_name)[0].' '.explode(' ', $child->full_name)[1],
                'email' => 'parent'.($i + 1).'@talent.test', 'phone' => '0122'.str_pad((string) (3000000 + $i * 4513), 7, '0', STR_PAD_LEFT),
                'role' => 'parent', 'password' => 'password',
            ]);
            $parent->children()->attach($child->id, ['relationship' => $i % 3 === 0 ? 'mother' : 'father']);
        }
    }

    private function enroll(Collection $students, Collection $batches): void
    {
        $byCode = $batches->keyBy('code');
        $plan = [
            'academic' => ['B-MATH-10A', 'B-PHYS-11B', 'B-ENG-12A'],
            'training' => ['B-AI-01', 'B-CYB-01', 'B-WEB-02', 'B-LANG-03', 'B-LANG-SUM'],
        ];

        // Demo student: Grade 10 math + the AI training course (unified person, two pathways).
        foreach (['B-MATH-10A', 'B-AI-01'] as $code) {
            $byCode[$code]->enrollments()->create(['student_id' => $students[0]->id, 'status' => 'active', 'enrolled_at' => $byCode[$code]->start_date]);
        }

        foreach ($students->slice(1) as $i => $student) {
            if ($student->status === 'withdrawn') {
                continue;
            }
            $options = $plan[$student->pathway];
            $picks = [$options[$i % count($options)]];
            if ($i % 4 === 0) {
                $picks[] = $options[($i + 1) % count($options)];
            }
            foreach ($picks as $code) {
                $batch = $byCode[$code];
                if ($batch->enrollments()->count() < $batch->capacity - 2) {
                    $batch->enrollments()->create(['student_id' => $student->id, 'status' => 'active', 'enrolled_at' => $batch->start_date]);
                }
            }
        }
    }

    private function attendance(Batch $batch): void
    {
        if ($batch->status === 'upcoming') {
            return;
        }

        $days = $batch->schedules->pluck('day_of_week')->all();
        $students = $batch->students()->get();
        $until = $batch->status === 'completed' ? $batch->end_date : now()->subDay();
        $rows = [];

        for ($date = $batch->start_date->copy(); $date->lte($until); $date->addDay()) {
            if (! in_array($date->dayOfWeek, $days, true)) {
                continue;
            }
            foreach ($students as $idx => $student) {
                // A couple of students per class are frequently absent, so reports have something to show.
                $weak = $idx % 7 === 3;
                $roll = mt_rand(1, 100);
                $status = match (true) {
                    $weak && $roll <= 30 => 'absent',
                    $roll <= 4 => 'absent',
                    $roll <= 11 => 'late',
                    $roll <= 13 => 'excused',
                    default => 'present',
                };
                $rows[] = [
                    'batch_id' => $batch->id, 'student_id' => $student->id, 'date' => $date->toDateString(),
                    'status' => $status, 'recorded_by' => $batch->teacher_id, 'created_at' => $date, 'updated_at' => $date,
                ];
            }
        }

        foreach (array_chunk($rows, 300) as $chunk) {
            AttendanceRecord::insert($chunk);
        }
    }

    private function assignments(Batch $batch): void
    {
        if ($batch->status === 'upcoming') {
            return;
        }

        $students = $batch->students()->get();
        $specs = [
            ['واجب الوحدة الأولى: تمارين تطبيقية', -12, true],
            ['مشروع قصير: تطبيق عملي على المحاضرات', -3, false],
            ['واجب الأسبوع القادم: مراجعة شاملة', 5, false],
        ];

        // Completed classes get their coursework dated relative to the class end, active ones relative to today.
        $completed = $batch->status === 'completed';
        $anchor = $completed ? $batch->end_date->copy() : now();

        foreach ($specs as $n => [$title, $offset, $graded]) {
            if ($completed && $offset > 0) {
                continue;
            }
            $assignment = Assignment::create([
                'batch_id' => $batch->id, 'teacher_id' => $batch->teacher_id, 'title' => $title,
                'instructions' => "يرجى حل جميع الأسئلة المرفقة وتسليمها قبل الموعد المحدد.\nيتم التقييم على الدقة والتنظيم.",
                'due_at' => $anchor->copy()->addDays($offset)->setTime(23, 59), 'max_score' => 20,
                'created_at' => $anchor->copy()->addDays($offset - 7),
            ]);
            $graded = $graded || $completed;

            if ($offset > 0) {
                continue;
            }

            foreach ($students as $idx => $student) {
                if (mt_rand(1, 100) > 85 && $student->user_id === null) {
                    continue;
                }
                $submitted = $assignment->due_at->copy()->subHours(mt_rand(-6, 72));
                $assignment->submissions()->create([
                    'student_id' => $student->id,
                    'body' => 'تم حل جميع التمارين المطلوبة مع توضيح خطوات الحل.',
                    'submitted_at' => $submitted,
                    'score' => $graded ? mt_rand(12, 20) : null,
                    'feedback' => $graded ? ['عمل ممتاز، استمر.', 'جيد، راجع السؤال الثالث.', 'أحسنت التنظيم.'][$idx % 3] : null,
                    'graded_at' => $graded ? $submitted->copy()->addDays(2) : null,
                ]);
            }
        }
    }

    private function exams(Batch $batch): void
    {
        if ($batch->status === 'upcoming') {
            return;
        }

        $students = $batch->students()->get();
        $completed = $batch->status === 'completed';
        $anchor = $completed ? $batch->end_date->copy() : now();
        $specs = $completed
            ? [['اختبار منتصف الدورة', 'midterm', -21], ['الاختبار النهائي', 'final', -2]]
            : [['اختبار قصير 1', 'quiz', -20], ['اختبار منتصف الفصل', 'midterm', -6], ['اختبار قصير 2', 'quiz', 6]];

        foreach ($specs as [$title, $type, $offset]) {
            $exam = Exam::create([
                'batch_id' => $batch->id, 'title' => $title, 'type' => $type, 'exam_date' => $anchor->copy()->addDays($offset),
                'starts_at' => '10:00', 'duration_minutes' => $type === 'quiz' ? 30 : 90,
                'max_score' => $type === 'quiz' ? 20 : 100, 'pass_score' => $type === 'quiz' ? 10 : 50,
                'results_published' => $offset < 0,
            ]);

            if ($offset > 0) {
                continue;
            }

            foreach ($students as $idx => $student) {
                $weak = $idx % 7 === 3;
                $pct = $weak ? mt_rand(45, 72) : mt_rand(68, 98);
                ExamResult::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'score' => round($exam->max_score * $pct / 100, 1)]);
            }
        }
    }

    private function materials(Batch $batch): void
    {
        $items = [
            ['ملخص الوحدة الأولى (PDF)', 'link', 'https://example.com/materials/unit-1.pdf'],
            ['فيديو شرح المحاضرة الثالثة', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['بنك الأسئلة والتدريبات', 'link', 'https://example.com/materials/question-bank'],
        ];
        foreach ($items as [$title, $type, $url]) {
            Material::create(['batch_id' => $batch->id, 'uploaded_by' => $batch->teacher_id, 'title' => $title, 'type' => $type, 'url' => $url]);
        }
    }

    private function finance(Collection $batches, Collection $branches): void
    {
        $methods = ['cash', 'cash', 'cash', 'vodafone_cash', 'instapay', 'bank_transfer'];

        foreach ($batches->where('status', '!=', 'upcoming') as $batch) {
            $price = (float) $batch->course->price;

            foreach ($batch->students()->get() as $idx => $student) {
                // Three monthly installments starting at the class start date.
                $share = round($price / 3, 2);
                for ($n = 0; $n < 3; $n++) {
                    $due = $batch->start_date->copy()->addMonths($n)->addDays(7);
                    $amount = $n === 2 ? round($price - $share * 2, 2) : $share;
                    $invoice = Invoice::create([
                        'number' => Invoice::nextNumber(), 'student_id' => $student->id, 'branch_id' => $student->branch_id,
                        'batch_id' => $batch->id, 'description' => $batch->course->title_ar.' — القسط '.($n + 1).' من 3',
                        'amount' => $amount, 'discount' => 0, 'issued_at' => $batch->start_date, 'due_date' => $due, 'status' => 'unpaid',
                    ]);

                    // Payment behaviour: most pay on time, some pay partially, a few are late.
                    $behaviour = ($idx + $n) % 9;
                    if ($due->isFuture() && $behaviour > 2) {
                        $invoice->recalculate();

                        continue;
                    }
                    $pay = match (true) {
                        $behaviour === 0 => 0,
                        $behaviour === 1 => round($amount / 2, 2),
                        default => $amount,
                    };
                    if ($pay > 0) {
                        Payment::create([
                            'receipt_number' => Payment::nextReceiptNumber(), 'invoice_id' => $invoice->id, 'student_id' => $student->id,
                            'amount' => $pay, 'method' => $methods[($idx + $n) % count($methods)],
                            'paid_at' => $due->copy()->subDays(mt_rand(0, 6))->setTime(mt_rand(9, 17), mt_rand(0, 59))->min(now()),
                            'received_by' => $this->admin->id,
                        ]);
                    }
                    $invoice->recalculate();
                }
            }
        }

        // Monthly operating expenses for the last 6 months.
        foreach ([$branches['BMZ-01'], $branches['MAG-02']] as $b => $branch) {
            for ($m = 5; $m >= 0; $m--) {
                $month = now()->subMonths($m)->startOfMonth();
                foreach ([['rent', 'إيجار المقر', 12000], ['salaries', 'رواتب الإداريين والعمال', 28000], ['utilities', 'كهرباء ومياه وإنترنت', 4200], ['supplies', 'أدوات مكتبية ومستلزمات معامل', 2500], ['marketing', 'إعلانات ممولة على فيسبوك', 3000]] as [$cat, $desc, $base]) {
                    $date = $month->copy()->addDays(mt_rand(1, 25));
                    if ($date->isFuture()) {
                        $date = now()->subDay();
                    }
                    Expense::create([
                        'branch_id' => $branch->id, 'category' => $cat, 'description' => $desc,
                        'amount' => round($base * 0.45 * ($b === 0 ? 1 : 0.55) * (mt_rand(90, 112) / 100)),
                        'spent_at' => $date, 'recorded_by' => $this->admin->id,
                    ]);
                }
            }
        }
    }

    private function certificates(?Batch $batch): void
    {
        if (! $batch) {
            return;
        }

        auth()->setUser($this->admin);
        $service = app(CertificateService::class);
        foreach ($service->eligibility($batch->load('course')) as $row) {
            if ($row['eligible']) {
                $service->issue($row['student'], $batch, 'شهادة إتمام: '.$batch->course->title_ar, 'Certificate of Completion: '.$batch->course->title_en, CertificateService::gradeFor($row['average']));
            }
        }
        auth()->forgetUser();
    }

    private function news(): void
    {
        $articles = [
            ['cyber-security-course', 'الأمن السيبراني', 'Cyber Security', 'دورة الأمن السيبراني: أساسيات حماية البيانات والأنظمة', 'Cyber Security Training: Fundamentals of Digital Protection',
                'تعرف على مفاهيم الأمن السيبراني والتوعية بالمخاطر الرقمية وأساسيات حماية الحسابات والبيانات والشبكات الحديثة.', 'Learn cybersecurity fundamentals, digital risks and practical ways to protect accounts, data and networks.',
                "أطلقت أكاديمية تالنت للخدمات التعليمية دورتها المتخصصة في الأمن السيبراني الموجهة للطلاب والمتدربين والمهتمين بالتحول الرقمي الآمن.\n\nتتضمن الدورة تدريبات عملية في المعامل على اكتشاف الثغرات، وأمن الشبكات، والتشفير، وإدارة كلمات المرور المؤسسية، مع بيئة محاكاة افتراضية لكل متدرب.\n\nيحصل المتدرب في نهاية الدورة على شهادة معتمدة برقم تسلسلي يمكن التحقق منه إلكترونياً.",
                "Talent Academy for Educational Services has launched its specialised cyber security course for students, trainees and anyone interested in safe digital transformation.\n\nThe course includes hands-on lab work on vulnerability discovery, network security, encryption and enterprise password management, with a sandboxed simulation environment for every trainee.\n\nGraduates receive an accredited certificate with a serial number that can be verified online.",
                'images/news_cyber.jpg', 'م. كريم عبد العزيز', 4],
            ['ai-diploma', 'الذكاء الاصطناعي', 'Artificial Intelligence', 'دبلومة الذكاء الاصطناعي وتطبيقاته العملية', 'Artificial Intelligence Diploma',
                'تأهيل شامل في الذكاء الاصطناعي وتطبيقاته العملية مع مشاريع حقيقية.', 'Comprehensive training in AI and its practical applications with real projects.',
                "تفتح الأكاديمية باب التسجيل في دبلومة الذكاء الاصطناعي وهندسة الأوامر التطبيقية.\n\nيتعلم المتدرب بناء روبوتات الدردشة وربط واجهات البرمجة وأتمتة تحليل البيانات من خلال مشاريع تطبيقية.",
                "Registration is now open for the Applied AI & Prompt Engineering Diploma.\n\nTrainees learn to build chatbots, integrate APIs and automate data analysis through applied projects.",
                'images/news_ai.jpg', 'م. أحمد الشريف', 9],
            ['american-diploma-2026', 'الدبلومة الأمريكية', 'American Diploma', 'فرص أكبر لمستقبل أفضل مع الدبلومة الأمريكية', 'More Opportunities with the American Diploma',
                'مجموعات جديدة لطلاب Grade 10-12 في الرياضيات والفيزياء واللغة الإنجليزية.', 'New groups for Grades 10-12 in Math, Physics and English.',
                "بدأت الأكاديمية استقبال طلاب الدبلومة الأمريكية للعام الدراسي الجديد في مجموعات صغيرة العدد.\n\nيشمل البرنامج التحضير لاختبارات EST و SAT مع اختبارات محاكاة دورية ومتابعة مستمرة لأولياء الأمور عبر البوابة الإلكترونية.",
                "The academy is now enrolling American Diploma students for the new school year in small groups.\n\nThe program includes EST and SAT preparation with regular mock exams and continuous parent follow-up through the online portal.",
                'images/news_diploma.jpg', 'د. سامح عبد الرازق', 14],
            ['new-training-programs', 'برامج جديدة', 'New Programs', 'برامج تدريبية جديدة في المحاسبة والمحادثة الإنجليزية', 'New Training Programs in Accounting and Business English',
                'يسعدنا الإعلان عن إضافة مجموعة جديدة من الدورات التدريبية المتخصصة.', 'We are pleased to announce a new set of specialised training courses.',
                "أضافت الأكاديمية برنامجين جديدين: المحاسبة وإدارة الأعمال الصغيرة، والمحادثة الإنجليزية لسوق العمل.\n\nتبدأ المجموعات خلال أسبوعين ويمكن التسجيل إلكترونياً من خلال الموقع.",
                "The academy has added two new programs: Accounting & Small Business Management, and Business English Conversation.\n\nGroups start within two weeks and you can apply online through the website.",
                'images/news_programs.jpg', 'إدارة الأكاديمية', 20],
        ];

        foreach ($articles as $a) {
            NewsArticle::create([
                'slug' => $a[0], 'category_ar' => $a[1], 'category_en' => $a[2], 'title_ar' => $a[3], 'title_en' => $a[4],
                'excerpt_ar' => $a[5], 'excerpt_en' => $a[6], 'body_ar' => $a[7], 'body_en' => $a[8],
                'image_path' => $a[9], 'author_name' => $a[10], 'is_published' => true, 'published_at' => now()->subDays($a[11]),
            ]);
        }
    }

    private function announcements(Collection $batches): void
    {
        Announcement::create(['title' => 'مواعيد اختبارات منتصف الفصل', 'body' => 'تبدأ اختبارات منتصف الفصل للمرحلة الثانوية الأسبوع القادم. يرجى مراجعة جدول كل مجموعة على البوابة.', 'audience' => 'students', 'author_id' => $this->admin->id, 'is_pinned' => true, 'created_at' => now()->subDays(2)]);
        Announcement::create(['title' => 'اجتماع أولياء الأمور', 'body' => 'ندعو السادة أولياء الأمور لحضور اجتماع متابعة المستوى يوم الخميس القادم الساعة 6 مساءً بالمقر الرئيسي.', 'audience' => 'parents', 'author_id' => $this->admin->id, 'created_at' => now()->subDays(4)]);
        Announcement::create(['title' => 'تسليم كشوف الدرجات', 'body' => 'برجاء الانتهاء من رصد درجات اختبارات منتصف الفصل ونشرها قبل نهاية الأسبوع.', 'audience' => 'teachers', 'author_id' => $this->admin->id, 'created_at' => now()->subDay()]);
        Announcement::create(['title' => 'عطلة رسمية', 'body' => 'الأكاديمية مغلقة يوم الجمعة كالمعتاد. البوابة الإلكترونية متاحة 24/7.', 'audience' => 'all', 'author_id' => $this->admin->id, 'created_at' => now()->subDays(6)]);
        $ai = $batches->firstWhere('code', 'B-AI-01');
        Announcement::create(['title' => 'تغيير قاعة المحاضرة', 'body' => 'محاضرة الأربعاء القادم ستكون في معمل الحاسب 2 بدلاً من معمل 1.', 'audience' => 'batch', 'batch_id' => $ai->id, 'author_id' => $ai->teacher_id, 'created_at' => now()->subHours(20)]);
    }

    private function admissions(Collection $courses, Collection $branches): void
    {
        $rows = [
            ['يوسف أحمد سليمان', '01098765432', 'academic', 'MATH-AD-102', 'Grade 10', 'أحمد سليمان', '01001234567', 'pending', 1],
            ['ندى محمد عبد الحميد', '01123456789', 'training', 'AI-PRO-201', 'طالبة جامعية', null, null, 'pending', 2],
            ['خالد عصام فاروق', '01234567890', 'training', 'WEB-PRO-203', 'خريج', null, null, 'contacted', 3],
            ['ريم أشرف حسني', '01556677889', 'academic', 'ENG-AD-101', 'Grade 12', 'أشرف حسني', '01009988776', 'pending', 0],
            ['عبد الله محمود ثابت', '01001112223', 'training', null, 'موظف', null, null, 'rejected', 9],
            ['هدى سمير عوض', '01112223334', 'training', 'LANG-PRO-204', 'خريجة', null, null, 'pending', 0],
        ];

        foreach ($rows as $r) {
            AdmissionRequest::create([
                'reference' => AdmissionRequest::newReference(), 'full_name' => $r[0], 'phone' => $r[1], 'pathway' => $r[2],
                'course_id' => $r[3] ? $courses[$r[3]]->id : null, 'branch_id' => $branches['BMZ-01']->id, 'grade_level' => $r[4],
                'guardian_name' => $r[5], 'guardian_phone' => $r[6], 'status' => $r[7],
                'reviewed_by' => $r[7] !== 'pending' ? $this->admin->id : null, 'reviewed_at' => $r[7] !== 'pending' ? now()->subDays(1) : null,
                'created_at' => now()->subDays($r[8])->subHours(3),
            ]);
        }
    }

    private function inquiries(): void
    {
        Inquiry::create(['name' => 'منى السيد', 'phone' => '01003334445', 'subject' => 'مواعيد مجموعات الفيزياء', 'message' => 'السلام عليكم، أريد معرفة مواعيد مجموعات الفيزياء لطلاب Grade 11 وهل يوجد مجموعة مسائية؟', 'status' => 'new', 'created_at' => now()->subHours(5)]);
        Inquiry::create(['name' => 'Ahmed Fouad', 'email' => 'ahmed.fouad@example.com', 'subject' => 'Corporate training', 'message' => 'We are a local company interested in a cyber security awareness workshop for 15 employees. Please share options and pricing.', 'status' => 'new', 'created_at' => now()->subDay()]);
        Inquiry::create(['name' => 'سامح جرجس', 'phone' => '01227778889', 'message' => 'هل يمكن تقسيط رسوم دبلومة تطوير الويب على أكثر من ثلاثة أشهر؟', 'status' => 'read', 'created_at' => now()->subDays(3)]);
    }

    private function activity(): void
    {
        foreach ([
            ['auth.login', 'أحمد سامي signed in', 0],
            ['settings.updated', 'Platform settings updated', 26],
            ['branch.updated', 'Branch MLW-03 updated', 50],
        ] as [$action, $description, $hoursAgo]) {
            ActivityLog::create(['user_id' => $this->admin->id, 'action' => $action, 'description' => $description, 'ip_address' => '127.0.0.1', 'created_at' => now()->subHours($hoursAgo)]);
        }
    }
}
