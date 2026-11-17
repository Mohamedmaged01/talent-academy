@php
    $pendingAdmissions = \App\Models\AdmissionRequest::where('status', 'pending')->count();
    $newInquiries = \App\Models\Inquiry::where('status', 'new')->count();
@endphp

@include('partials.nav-item', ['route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')])

<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('People') }}</p>
@include('partials.nav-item', ['route' => 'admin.students.index', 'match' => 'admin.students.*', 'icon' => 'graduation-cap', 'label' => __('Students & Trainees')])
@include('partials.nav-item', ['route' => 'admin.teachers.index', 'match' => 'admin.teachers.*', 'icon' => 'presentation', 'label' => __('Teachers & Trainers')])
@include('partials.nav-item', ['route' => 'admin.guardians.index', 'match' => 'admin.guardians.*', 'icon' => 'users', 'label' => __('Parents')])
@include('partials.nav-item', ['route' => 'admin.admissions.index', 'match' => 'admin.admissions.*', 'icon' => 'user-plus', 'label' => __('Admissions'), 'badge' => $pendingAdmissions ?: null])

<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Academics') }}</p>
@include('partials.nav-item', ['route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'book-open', 'label' => __('Programs & Courses')])
@include('partials.nav-item', ['route' => 'admin.batches.index', 'match' => 'admin.batches.*', 'icon' => 'users-round', 'label' => __('Classes & Groups')])
@include('partials.nav-item', ['route' => 'admin.timetable', 'icon' => 'calendar-days', 'label' => __('Timetable')])
@include('partials.nav-item', ['route' => 'admin.attendance', 'icon' => 'clipboard-check', 'label' => __('Attendance Reports')])
@include('partials.nav-item', ['route' => 'admin.certificates.index', 'match' => 'admin.certificates.*', 'icon' => 'award', 'label' => __('Certificates')])

<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Finance') }}</p>
@include('partials.nav-item', ['route' => 'admin.finance', 'icon' => 'chart-pie', 'label' => __('Financial Overview')])
@include('partials.nav-item', ['route' => 'admin.invoices.index', 'match' => 'admin.invoices.*', 'icon' => 'file-text', 'label' => __('Invoices')])
@include('partials.nav-item', ['route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'icon' => 'wallet', 'label' => __('Payments')])
@include('partials.nav-item', ['route' => 'admin.expenses.index', 'icon' => 'receipt', 'label' => __('Expenses')])

<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Communication') }}</p>
@include('partials.nav-item', ['route' => 'admin.announcements.index', 'icon' => 'megaphone', 'label' => __('Announcements')])
@include('partials.nav-item', ['route' => 'admin.inquiries.index', 'icon' => 'inbox', 'label' => __('Contact Messages'), 'badge' => $newInquiries ?: null])
@include('partials.nav-item', ['route' => 'admin.news.index', 'match' => 'admin.news.*', 'icon' => 'newspaper', 'label' => __('News & Blog')])

<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('System') }}</p>
@include('partials.nav-item', ['route' => 'admin.branches.index', 'match' => 'admin.branches.*', 'icon' => 'building-2', 'label' => __('Branches')])
@include('partials.nav-item', ['route' => 'admin.users.index', 'icon' => 'shield-check', 'label' => __('User Accounts')])
@include('partials.nav-item', ['route' => 'admin.activity', 'icon' => 'history', 'label' => __('Activity Log')])
@include('partials.nav-item', ['route' => 'admin.settings.edit', 'icon' => 'settings', 'label' => __('Settings')])
