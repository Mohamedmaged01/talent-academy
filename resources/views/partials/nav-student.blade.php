@php
    $heading = fn (string $label) => '<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">'.e($label).'</p>';
@endphp

@include('partials.nav-item', ['route' => 'student.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')])
@include('partials.nav-item', ['route' => 'student.schedule', 'icon' => 'calendar-days', 'label' => __('My Schedule')])
@include('partials.nav-item', ['route' => 'student.live', 'icon' => 'video', 'label' => __('Live Classes')])
@include('partials.nav-item', ['route' => 'student.courses', 'match' => 'student.courses*', 'icon' => 'book-open', 'label' => __('My Courses')])

{!! $heading(__('Learning')) !!}
@include('partials.nav-item', ['route' => 'student.lectures', 'match' => 'student.lectures*', 'icon' => 'clapperboard', 'label' => __('Recorded Lectures')])
@include('partials.nav-item', ['route' => 'student.assignments.index', 'match' => 'student.assignments.*', 'icon' => 'notebook-pen', 'label' => __('Assignments')])
@include('partials.nav-item', ['route' => 'student.quizzes.index', 'match' => 'student.quizzes.*', 'icon' => 'monitor-check', 'label' => __('Online Exams')])
@include('partials.nav-item', ['route' => 'library.index', 'icon' => 'library', 'label' => __('Digital Library')])
@include('partials.nav-item', ['route' => 'student.grades', 'icon' => 'chart-column', 'label' => __('Grades')])
@include('partials.nav-item', ['route' => 'student.attendance', 'icon' => 'clipboard-check', 'label' => __('Attendance')])
@include('partials.nav-item', ['route' => 'student.certificates', 'match' => 'student.certificates*', 'icon' => 'award', 'label' => __('Certificates')])

{!! $heading(__('My account')) !!}
@include('partials.nav-item', ['route' => 'student.payments', 'icon' => 'wallet', 'label' => __('Fees & Payments')])
@include('partials.nav-item', ['route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'mail', 'label' => __('Messages'), 'badge' => auth()->user()->unreadThreadsCount() ?: null])
@include('partials.nav-item', ['route' => 'student.announcements', 'icon' => 'megaphone', 'label' => __('Announcements')])
@include('partials.nav-item', ['route' => 'downloads.portal', 'icon' => 'download', 'label' => __('Downloads Center')])
@include('partials.nav-item', ['route' => 'support.index', 'match' => 'support.*', 'icon' => 'life-buoy', 'label' => __('Help & Support')])
