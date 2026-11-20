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

## Deploy to a server (Plesk / IIS or Apache)

The compiled CSS and JavaScript are committed in `public/build`, so the server does not need Node.js. **After changing anything in `resources/` (views, CSS or JS), run `npm run build` and commit `public/build` along with your change.** Otherwise the server keeps the old styles, and any new Tailwind classes will have no styling.

On the server:

1. Point the site's document root at the `public/` folder, not the project folder.
2. Run `composer install --no-dev --optimize-autoloader`.
3. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=https://your-domain`. Then run `php artisan key:generate`. **Keep a copy of `APP_KEY` somewhere safe and offline:** backups are encrypted with it and cannot be restored without it.
4. Set up the database:
   - SQLite: create the empty file `database/database.sqlite`.
   - MySQL: set the `DB_*` values in `.env`.

   Then run `php artisan migrate --force`. Add `--seed` only for a demo site: every seeded account uses the password `password`, including the admin.
5. Run `php artisan storage:link`, then `php artisan optimize`.
6. Give the web server user write access to `storage/` and `bootstrap/cache/`. On IIS that is the site's application pool user.
7. Schedule `php artisan schedule:run` to run every minute (cron on Linux, Task Scheduler / Plesk "Scheduled Tasks" on Windows). It takes the daily encrypted backup at 02:30 and keeps the last 14.
8. Optional: set `GEMINI_API_KEY` in `.env` to switch on the Talent AI assistant.

On IIS (Plesk for Windows), `public/web.config` routes every request to Laravel; it needs the IIS URL Rewrite module, which Plesk installs. On Apache, `public/.htaccess` does the same.

### Upgrading an existing installation

```bash
php artisan migrate --force
php artisan ledger:rebuild    # posts every existing invoice, payment and expense to the new ledger (run once)
php artisan optimize
```

Existing administrator accounts keep full access: an administrator without a role is a full administrator. Create roles under **Roles & Permissions** and assign them when you want to limit someone.

## Demo accounts

Every seeded account uses the password `password`. The login page shows quick-fill buttons when `APP_ENV=local`.

| Role | Email | What you'll see |
|---|---|---|
| Admin | `admin@talent.test` | Full back office (full administrator) |
| Accountant | `accountant@talent.test` | Finance, payroll and ledger only; has a pending access request |
| Registrar | `registrar@talent.test` | Admissions, students, classes; temporary finance access by delegation |
| Branch manager | `maghagha@talent.test` | Limited to the Maghagha branch |
| Teacher | `teacher@talent.test` | 2 classes (math + accounting), attendance, assignments, exams, question bank, lectures |
| Student | `student@talent.test` | Enrolled in an academic class **and** a training course; an online quiz is open today |
| Parent | `parent@talent.test` | Two linked children, a conversation with the math teacher |

The seeded data is relative to today's date, so dashboards always look current. Re-run `php artisan migrate:fresh --seed` to reset it.

## What's in it

**Public site:** home (hero, quick links, two pathways, news, stats, contact form), program catalog with search, program pages with open classes, news, online application with reference number, certificate verification by serial, downloads center, about, privacy and terms. Hero texts, images, the about intro and a notice bar are editable from the admin.

**Admin:**
- **Dashboard** (KPIs, revenue vs. expenses, 30-day attendance, today's schedule, overdue fees, latest applications, activity) and **Reports & analytics** (overview, academic, training, financial with receivables aging and income statement, branches and faculty), each exportable to CSV.
- **People:** students (CSV export, notes on the file), teachers/trainers, parents linked to children, admissions (review, then accept into a student record and a class).
- **Academics:** programs, classes with weekly schedules, online meeting links and capacity-checked enrollment, timetable, attendance reports, exams across the academy (publish/hide results), question bank, recorded lectures, digital library, downloads center.
- **Certificates:** eligibility check, issuing a whole class at once, teacher nominations to approve or reject, revoking, printable certificate with a QR code.
- **Finance:** invoices with installment plans, payments with printable receipts and WhatsApp reminders, expenses, financial overview, **payroll** (monthly drafts from each teacher's salary, payslips), and a **double-entry general ledger** (automatic postings, manual entries and reversals, chart of accounts, account ledgers, trial balance, income statement).
- **Communication:** private messages, group messages with saved templates, announcements, contact messages, support tickets, news and site content with a media library, in-app notifications.
- **System:** branches, user accounts, **roles & permissions** (view/manage per section, optional branch limit), **delegations and access requests** (temporary permissions), settings, **webhooks** for other systems, **encrypted backups** with restore, **security & integrity** (sign-in history, certificate-check log, live consistency checks), activity log, **Talent AI assistant** (Google Gemini, server-side).

**Teacher:** dashboard, weekly schedule, my classes and roster, my students with notes (optionally shared with parents), attendance, materials, recorded lectures, meeting links for live sessions, class announcements, assignments with grading, paper and online exams, question bank with bulk import, certificate nominations, class reports with CSV export, payslips, messages, support, library, downloads, AI assistant.

**Student:** dashboard, schedule, live classes (Join button 15 minutes before class), courses and materials, recorded lectures with progress, assignment submission (text and file), timed online exams with answer review, grades, attendance, fees and payments, certificates, library, downloads, messages, support.

**Parent:** per-child attendance, published results, fees, schedule, teachers' shared notes, announcements, messages with teachers, support, downloads.

## Rules enforced on the server

- Each role can only open its own portal. Teachers can only touch classes assigned to them; parents only see their own children; students only see their own classes.
- Administrator roles: reading a section needs "view", changing anything needs "manage". Nobody can create, assign or delegate permissions they do not hold, and only a full administrator can create another one. Branch-limited administrators get a 403 on any record from another branch.
- Messaging: students can message their teachers and the administration; parents can message their children's teachers and the administration; teachers can message their students, those students' parents and the administration. A conversation is visible only to its participants.
- Online exams are graded on the server. An attempt has the exam's time limit (capped by the closing time); answers arriving more than 2 minutes late are discarded. Questions are locked once a student has started.
- Certificates require **≥ 80% attendance and ≥ 70% exam average** (`App\Services\CertificateService`). Approving a teacher's nomination re-checks the rules.
- Payments run in a locked transaction; over-collection is rejected and invoice status is recalculated (`App\Services\PaymentService`).
- Every invoice, payment and expense posts a balanced journal entry (`App\Services\LedgerService`); unbalanced manual entries are rejected, and entries are reversed, never edited. Paid payroll records and the expenses they created cannot be changed or deleted.
- Records with history are never hard-deleted. A branch with records is archived, a student with invoices is marked withdrawn, a class with students is cancelled, and a program with classes is unpublished.
- Login is rate-limited (5 attempts per minute) and every sign-in, sign-out and failure is logged. Deactivating a user ends their sessions. CSV export neutralises spreadsheet formulas.
- Exam results stay hidden from students and parents until published.
- Backups are AES-256 encrypted with the application key and SHA-256 checksummed; a restore verifies the checksum and takes a safety copy first. Library and downloads files are served through the app, so audience rules cannot be bypassed.
- The AI assistant only sends aggregate figures to Google, never names, phone numbers or other personal details. Webhooks are signed with HMAC-SHA256 (`X-Talent-Signature`).

## Console commands

```bash
php artisan backups:run --keep=14   # encrypted backup now (also scheduled daily)
php artisan ledger:rebuild          # re-post invoices, payments and expenses to the ledger
php artisan delegations:expire      # close access requests pending for 30+ days (scheduled daily)
```

## Tests

```bash
vendor/bin/phpunit      # 80 tests
```

`tests/Feature/PageSmokeTest.php` renders every page for every role in both languages. The other test files cover access control, finance, end-to-end workflows, learning content (online exams, lectures, question import), communication, teacher tools, accounting and payroll, and the platform features (roles, delegations, branch scope, backups, webhooks, assistant, security log, integrity checks).
