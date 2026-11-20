<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A public certificate-verification request: valid, revoked or not found. */
class CertificateLookup extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['serial', 'certificate_id', 'result', 'ip_address'];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
