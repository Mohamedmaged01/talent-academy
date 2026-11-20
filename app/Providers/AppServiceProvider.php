<?php

namespace App\Providers;

use App\Models\AdmissionRequest;
use App\Models\Certificate;
use App\Models\Expense;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Observers\FinanceObserver;
use App\Observers\WebhookObserver;
use App\Services\LedgerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LedgerService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Brute-force protection: 5 attempts per minute per email + IP.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // The assistant calls a paid external API; keep a runaway browser tab from burning through quota.
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id));

        foreach ([Invoice::class, Payment::class, Expense::class] as $model) {
            $model::observe(FinanceObserver::class);
        }

        foreach ([AdmissionRequest::class, Student::class, Certificate::class, Inquiry::class, SupportTicket::class] as $model) {
            $model::observe(WebhookObserver::class);
        }
    }
}
