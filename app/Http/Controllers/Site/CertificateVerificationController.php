<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateLookup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(Request $request): View
    {
        $serial = strtoupper(trim((string) $request->query('serial')));
        $certificate = null;

        if ($serial !== '') {
            $certificate = Certificate::with(['student', 'course', 'batch'])->where('serial', $serial)->first();

            CertificateLookup::create([
                'serial' => mb_substr($serial, 0, 64),
                'certificate_id' => $certificate?->id,
                'result' => $certificate ? ($certificate->isValid() ? 'valid' : 'revoked') : 'not_found',
                'ip_address' => $request->ip(),
            ]);
        }

        return view('site.verify', compact('serial', 'certificate'));
    }
}
