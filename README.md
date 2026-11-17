# Talent Academy (Laravel)

The Talent Academy platform rebuilt as a server-rendered Laravel application: public website, admissions, and four portals (admin, teacher/trainer, student/trainee, parent). Arabic is the default language (RTL); English is one click away.

**Stack:** Laravel 12 · PHP 8.2+ · Blade · Tailwind CSS 4 · Alpine.js · Chart.js · SQLite by default (MySQL works unchanged).

## Run it locally

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
npm run build               # or `npm run dev` while editing views/CSS
php artisan serve           # http://127.0.0.1:8000
```

To use MySQL instead (for example XAMPP), set `DB_CONNECTION=mysql` plus `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, then run `php artisan migrate:fresh --seed` again.

## Demo accounts

Every seeded account uses the password `password`. The login page shows quick-fill buttons when `APP_ENV=local`.

| Role | Email | What you'll see |
|---|---|---|
| Admin | `admin@talent.test` | Full back office |
| Teacher | `teacher@talent.test` | 2 classes (math + accounting), attendance, assignments, exams |
| Student | `student@talent.test` | Enrolled in an academic class **and** a training course (one unified record) |
| Parent | `parent@talent.test` | Two linked children |

The seeded data is relative to today's date, so dashboards always look current. Re-run `php artisan migrate:fresh --seed` to reset it.

## What's in it

**Public site:** home (hero, quick links, two pathways, news, stats, contact form), program catalog with search, program pages with open classes, news, online application with reference number, certificate verification by serial, about, privacy and terms.

**Admin:** dashboard (KPIs, revenue vs. expenses chart, 30-day attendance, today's schedule, overdue fees, latest applications, activity). It also covers:
- students (CSV export, optional portal login), teachers/trainers, and parents linked to children
- programs, classes with weekly schedules and capacity-checked enrollment, timetable, attendance reports
- admissions: review, then accept into a student record and a class
- invoices with installment plans, payments with printable receipts, expenses, financial overview
- certificates (eligibility check, issuing a whole class at once, revoking, printable certificate with a QR code)
- news, announcements, contact messages, user accounts (deactivate, reset password), settings, and the activity log

**Teacher:** dashboard, weekly schedule, my classes and roster, attendance taking, materials (files/links), class announcements, assignments with grading, and exams with results entry and publishing.

**Student:** dashboard, schedule, courses and materials, assignment submission (text and file), grades, attendance, fees and payments, certificates.

**Parent:** per-child attendance, published results, fees, schedule and announcements.

## Rules enforced on the server

- Each role can only open its own portal. Teachers can only touch classes assigned to them; parents only see their own children; students only see their own classes.
- Certificates require **≥ 80% attendance and ≥ 70% exam average** (`App\Services\CertificateService`).
- Payments run in a locked transaction; over-collection is rejected and invoice status is recalculated (`App\Services\PaymentService`).
- Records with history are never hard-deleted. A branch with records is archived, a student with invoices is marked withdrawn, a class with students is cancelled, and a program with classes is unpublished.
- Login is rate-limited (5 attempts per minute). Deactivating a user ends their sessions. CSV export neutralises spreadsheet formulas.
- Exam results stay hidden from students and parents until the teacher publishes them.

## Tests

```bash
vendor/bin/phpunit      # 41 tests
```

`tests/Feature/PageSmokeTest.php` renders every page for every role in both languages. The other test files cover access control, finance, and end-to-end workflows (application → student → class, assignment → grade, eligibility → certificate → verification).

## Not ported from the React prototype

These modules from the original prototype are not in this build yet:
- digital library and download center
- payroll
- third-party integrations and webhooks
- backup and disaster-recovery console
- fine-grained RBAC with per-branch admins and delegations
- the AI chat assistant
- page media manager
- online question-bank exams

Everything that remains works against the real database instead of mock data.
