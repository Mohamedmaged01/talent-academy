<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Double-entry ledger (chart of accounts, journal entries, journal lines) and payroll.
 * The system accounts are inserted here, not in the seeder, because the ledger
 * observers need them on every installation, including production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('type');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('kind')->default('auto');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('source');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('entry_date');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('memo')->nullable();
        });

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 7);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });

        $now = now();
        DB::table('accounts')->insert(array_map(fn ($a) => [
            'code' => $a[0], 'name_ar' => $a[1], 'name_en' => $a[2], 'type' => $a[3],
            'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], [
            ['1000', 'الخزينة النقدية', 'Cash on hand', 'asset'],
            ['1010', 'البنك والمحافظ الإلكترونية', 'Bank & e-wallets', 'asset'],
            ['1100', 'ذمم الطلاب المدينة', 'Student receivables', 'asset'],
            ['2000', 'مستحقات وأرصدة دائنة', 'Accrued liabilities', 'liability'],
            ['3000', 'رأس المال', "Owner's equity", 'equity'],
            ['4000', 'إيرادات الرسوم الدراسية والتدريبية', 'Tuition & training revenue', 'revenue'],
            ['4100', 'إيرادات أخرى', 'Other revenue', 'revenue'],
            ['5000', 'الرواتب والأجور', 'Salaries & wages', 'expense'],
            ['5100', 'الإيجارات', 'Rent', 'expense'],
            ['5200', 'المرافق', 'Utilities', 'expense'],
            ['5300', 'المستلزمات', 'Supplies', 'expense'],
            ['5400', 'التسويق', 'Marketing', 'expense'],
            ['5500', 'الصيانة', 'Maintenance', 'expense'],
            ['5900', 'مصروفات أخرى', 'Other expenses', 'expense'],
        ]));
    }

    public function down(): void
    {
        foreach (['payroll_records', 'journal_lines', 'journal_entries', 'accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
