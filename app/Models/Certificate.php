<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasTranslations;

    protected $fillable = [
        'serial', 'student_id', 'course_id', 'batch_id', 'title_ar', 'title_en',
        'grade', 'issued_at', 'status', 'revoked_reason', 'issued_by',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public static function newSerial(): string
    {
        do {
            $serial = 'TA-CERT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (static::where('serial', $serial)->exists());

        return $serial;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function verificationUrl(): string
    {
        return route('certificates.verify', ['serial' => $this->serial]);
    }

    /** Inline SVG QR code pointing at the public verification page (generated locally, no external service). */
    public function qrSvg(int $size = 110): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 0),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd(),
        );

        $svg = (new \BaconQrCode\Writer($renderer))->writeString($this->verificationUrl());

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }
}
