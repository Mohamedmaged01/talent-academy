@php
    $heading = fn (string $label) => '<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">'.e($label).'</p>';
    $toGrade = \App\Models\AssignmentSubmission::whereNull('graded_at')->whereHas('assignment.batch', fn ($q) => $q->where('teacher_id', auth()->id()))->count();
@endphp

@include('partials.nav-item', ['route' => 'teacher.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')])
@include('partials.nav-item', ['route' => 'teacher.schedule', 'icon' => 'calendar-days', 'label' => __('My Schedule')])
@include('partials.nav-item', ['route' => 'teacher.batches.index', 'match' => ['teacher.batches.*', 'teacher.attendance.*'], 'icon' => 'users-round', 'label' => __('My Classes')])
@include('partials.nav-item', ['route' => 'teacher.students.index', 'match' => 'teacher.students.*', 'icon' => 'graduation-cap', 'label' => __('My Students')])

{!! $heading(__('Teaching')) !!}
@include('partials.nav-item', ['route' => 'teacher.assignments.index', 'match' => 'teacher.assignments.*', 'icon' => 'notebook-pen', 'label' => __('Assignments'), 'badge' => $toGrade ?: null])
@include('partials.nav-item', ['route' => 'teacher.exams.index', 'match' => 'teacher.exams.*', 'icon' => 'file-check', 'label' => __('Exams & Grades')])
@include('partials.nav-item', ['route' => 'teacher.questions.index', 'match' => 'teacher.questions.*', 'icon' => 'circle-help', 'label' => __('Question Bank')])
@include('partials.nav-item', ['route' => 'teacher.lectures.index', 'match' => 'teacher.lectures.*', 'icon' => 'clapperboard', 'label' => __('Recorded Lectures')])
@include('partials.nav-item', ['route' => 'library.index', 'icon' => 'library', 'label' => __('Digital Library')])
@include('partials.nav-item', ['route' => 'teacher.certificates', 'icon' => 'award', 'label' => __('Certificates')])
@include('partials.nav-item', ['route' => 'teacher.reports', 'icon' => 'chart-column', 'label' => __('Reports')])

{!! $heading(__('Communication')) !!}
@include('partials.nav-item', ['route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'mail', 'label' => __('Messages'), 'badge' => auth()->user()->unreadThreadsCount() ?: null])
@include('partials.nav-item', ['route' => 'teacher.announcements', 'icon' => 'megaphone', 'label' => __('Announcements')])
@include('partials.nav-item', ['route' => 'downloads.portal', 'icon' => 'download', 'label' => __('Downloads Center')])
@include('partials.nav-item', ['route' => 'teacher.payslips', 'match' => 'teacher.payslips*', 'icon' => 'banknote', 'label' => __('My Payslips')])
@include('partials.nav-item', ['route' => 'teacher.assistant', 'icon' => 'sparkles', 'label' => __('Talent AI Assistant')])
@include('partials.nav-item', ['route' => 'support.index', 'match' => 'support.*', 'icon' => 'life-buoy', 'label' => __('Help & Support')])
