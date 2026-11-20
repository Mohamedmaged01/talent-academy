<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An outgoing webhook: platform events are POSTed as signed JSON to an external URL. */
class Webhook extends Model
{
    public const EVENTS = [
        'admission.submitted' => 'New online application',
        'student.created' => 'Student record created',
        'invoice.created' => 'Invoice issued',
        'payment.received' => 'Payment received',
        'certificate.issued' => 'Certificate issued',
        'exam.results_published' => 'Exam results published',
        'inquiry.received' => 'Contact message received',
        'support.ticket_opened' => 'Support ticket opened',
    ];

    protected $fillable = ['name', 'url', 'secret', 'events', 'is_active', 'last_status', 'last_delivered_at'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
            'secret' => 'encrypted',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->latest('created_at')->latest('id');
    }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
