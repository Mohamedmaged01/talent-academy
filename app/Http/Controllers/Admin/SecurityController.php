<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateLookup;
use App\Models\LoginEvent;
use App\Services\IntegrityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sign-in history, public certificate lookups and live data-integrity checks. */
class SecurityController extends Controller
{
    public const TABS = ['logins', 'verifications', 'integrity'];

    public function __invoke(Request $request, IntegrityService $integrity): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'logins';

        $data = match ($tab) {
            'logins' => [
                'events' => LoginEvent::with('user')
                    ->when($request->query('filter') === 'failed', fn ($q) => $q->where('successful', false))
                    ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('email', 'like', "%{$v}%")->orWhere('ip_address', 'like', "%{$v}%")))
                    ->latest('created_at')->latest('id')
                    ->paginate(30)->withQueryString(),
                'stats' => [
                    'failed_24h' => LoginEvent::where('successful', false)->where('created_at', '>=', now()->subDay())->count(),
                    'success_24h' => LoginEvent::where('successful', true)->where('event', 'login')->where('created_at', '>=', now()->subDay())->count(),
                    'suspicious_ips' => LoginEvent::where('successful', false)->where('created_at', '>=', now()->subDay())
                        ->selectRaw('ip_address, count(*) as attempts')->groupBy('ip_address')->havingRaw('count(*) >= 5')->orderByDesc('attempts')->get(),
                ],
            ],
            'verifications' => [
                'lookups' => CertificateLookup::with('certificate.student')->latest('created_at')->latest('id')->paginate(30),
                'stats' => CertificateLookup::where('created_at', '>=', now()->subDays(30))->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result'),
            ],
            'integrity' => ['checks' => $integrity->run()],
        };

        return view('admin.security', $data + ['tab' => $tab]);
    }
}
