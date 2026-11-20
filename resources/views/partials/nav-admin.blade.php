@php
    $me = auth()->user();
    $can = fn (string $module) => $me->canAdmin($module);
    $pendingAdmissions = $can('admissions') ? \App\Models\AdmissionRequest::where('status', 'pending')->count() : 0;
    $newInquiries = $can('communication') ? \App\Models\Inquiry::where('status', 'new')->count() : 0;
    $openTickets = $can('support') ? \App\Models\SupportTicket::open()->count() : 0;
    $pendingNominations = $can('certificates') ? \App\Models\CertificateNomination::where('status', 'pending')->count() : 0;
    $heading = fn (string $label) => '<p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">'.e($label).'</p>';
@endphp

@include('partials.nav-item', ['route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')])
@if ($can('reports'))
    @include('partials.nav-item', ['route' => 'admin.reports', 'icon' => 'chart-no-axes-combined', 'label' => __('Reports & Analytics')])
@endif

@if ($can('students') || $can('teachers') || $can('parents') || $can('admissions'))
    {!! $heading(__('People')) !!}
    @if ($can('students'))@include('partials.nav-item', ['route' => 'admin.students.index', 'match' => 'admin.students.*', 'icon' => 'graduation-cap', 'label' => __('Students & Trainees')])@endif
    @if ($can('teachers'))@include('partials.nav-item', ['route' => 'admin.teachers.index', 'match' => 'admin.teachers.*', 'icon' => 'presentation', 'label' => __('Teachers & Trainers')])@endif
    @if ($can('parents'))@include('partials.nav-item', ['route' => 'admin.guardians.index', 'match' => 'admin.guardians.*', 'icon' => 'users', 'label' => __('Parents')])@endif
    @if ($can('admissions'))@include('partials.nav-item', ['route' => 'admin.admissions.index', 'match' => 'admin.admissions.*', 'icon' => 'user-plus', 'label' => __('Admissions'), 'badge' => $pendingAdmissions ?: null])@endif
@endif

@if ($can('academics') || $can('exams') || $can('certificates') || $can('library'))
    {!! $heading(__('Academics')) !!}
    @if ($can('academics'))
        @include('partials.nav-item', ['route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'book-open', 'label' => __('Programs & Courses')])
        @include('partials.nav-item', ['route' => 'admin.batches.index', 'match' => 'admin.batches.*', 'icon' => 'users-round', 'label' => __('Classes & Groups')])
        @include('partials.nav-item', ['route' => 'admin.timetable', 'icon' => 'calendar-days', 'label' => __('Timetable')])
        @include('partials.nav-item', ['route' => 'admin.attendance', 'icon' => 'clipboard-check', 'label' => __('Attendance Reports')])
    @endif
    @if ($can('exams'))@include('partials.nav-item', ['route' => 'admin.exams.index', 'match' => ['admin.exams.*', 'admin.questions.*', 'admin.lectures.*'], 'icon' => 'file-check', 'label' => __('Exams & Question Bank')])@endif
    @if ($can('certificates'))@include('partials.nav-item', ['route' => 'admin.certificates.index', 'match' => ['admin.certificates.*', 'admin.nominations.*'], 'icon' => 'award', 'label' => __('Certificates'), 'badge' => $pendingNominations ?: null])@endif
    @if ($can('library'))@include('partials.nav-item', ['route' => 'admin.library.index', 'match' => ['admin.library.*', 'admin.downloads.*'], 'icon' => 'library', 'label' => __('Library & Downloads')])@endif
@endif

@if ($can('finance') || $can('payroll') || $can('ledger'))
    {!! $heading(__('Finance')) !!}
    @if ($can('finance'))
        @include('partials.nav-item', ['route' => 'admin.finance', 'icon' => 'chart-pie', 'label' => __('Financial Overview')])
        @include('partials.nav-item', ['route' => 'admin.invoices.index', 'match' => 'admin.invoices.*', 'icon' => 'file-text', 'label' => __('Invoices')])
        @include('partials.nav-item', ['route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'icon' => 'wallet', 'label' => __('Payments')])
        @include('partials.nav-item', ['route' => 'admin.expenses.index', 'icon' => 'receipt', 'label' => __('Expenses')])
    @endif
    @if ($can('payroll'))@include('partials.nav-item', ['route' => 'admin.payroll.index', 'match' => 'admin.payroll.*', 'icon' => 'banknote', 'label' => __('Payroll')])@endif
    @if ($can('ledger'))@include('partials.nav-item', ['route' => 'admin.ledger.index', 'match' => 'admin.ledger.*', 'icon' => 'book-open-check', 'label' => __('General Ledger')])@endif
@endif

{!! $heading(__('Communication')) !!}
@include('partials.nav-item', ['route' => 'messages.index', 'match' => 'messages.*', 'icon' => 'mail', 'label' => __('Messages'), 'badge' => $me->unreadThreadsCount() ?: null])
@if ($can('communication'))
    @include('partials.nav-item', ['route' => 'admin.announcements.index', 'match' => ['admin.announcements.*', 'admin.broadcast.*'], 'icon' => 'megaphone', 'label' => __('Announcements')])
    @include('partials.nav-item', ['route' => 'admin.inquiries.index', 'icon' => 'inbox', 'label' => __('Contact Messages'), 'badge' => $newInquiries ?: null])
@endif
@if ($can('support'))@include('partials.nav-item', ['route' => 'admin.support.index', 'match' => 'admin.support.*', 'icon' => 'life-buoy', 'label' => __('Support Tickets'), 'badge' => $openTickets ?: null])@endif
@if ($can('content'))
    @include('partials.nav-item', ['route' => 'admin.news.index', 'match' => 'admin.news.*', 'icon' => 'newspaper', 'label' => __('News & Blog')])
    @include('partials.nav-item', ['route' => 'admin.content.edit', 'match' => ['admin.content.*', 'admin.media.*'], 'icon' => 'images', 'label' => __('Site Content & Media')])
@endif

{!! $heading(__('System')) !!}
@if ($can('branches'))@include('partials.nav-item', ['route' => 'admin.branches.index', 'match' => 'admin.branches.*', 'icon' => 'building-2', 'label' => __('Branches')])@endif
@if ($can('users'))
    @include('partials.nav-item', ['route' => 'admin.users.index', 'icon' => 'user-cog', 'label' => __('User Accounts')])
    @include('partials.nav-item', ['route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'icon' => 'shield-check', 'label' => __('Roles & Permissions')])
@endif
@include('partials.nav-item', ['route' => 'admin.delegations.index', 'icon' => 'key-round', 'label' => __('Delegations & Access')])
@if ($can('settings'))
    @include('partials.nav-item', ['route' => 'admin.settings.edit', 'icon' => 'settings', 'label' => __('Settings')])
    @include('partials.nav-item', ['route' => 'admin.integrations.index', 'icon' => 'webhook', 'label' => __('Integrations')])
@endif
@if ($can('backups'))@include('partials.nav-item', ['route' => 'admin.backups.index', 'icon' => 'database-backup', 'label' => __('Backup & Recovery')])@endif
@if ($can('security'))
    @include('partials.nav-item', ['route' => 'admin.security', 'icon' => 'shield-alert', 'label' => __('Security & Integrity')])
    @include('partials.nav-item', ['route' => 'admin.activity', 'icon' => 'history', 'label' => __('Activity Log')])
@endif
@if ($can('assistant'))@include('partials.nav-item', ['route' => 'admin.assistant', 'icon' => 'sparkles', 'label' => __('Talent AI Assistant')])@endif
