<?php

namespace App\Observers;

use App\Models\AdmissionRequest;
use App\Models\Certificate;
use App\Models\Inquiry;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Support\Webhooks;
use Illuminate\Database\Eloquent\Model;

/** Announces newly created records to webhooks. Payloads carry only what a receiver needs. */
class WebhookObserver
{
    public function created(Model $model): void
    {
        [$event, $data] = match (true) {
            $model instanceof AdmissionRequest => ['admission.submitted', [
                'reference' => $model->reference, 'full_name' => $model->full_name, 'phone' => $model->phone,
                'pathway' => $model->pathway, 'course_code' => $model->course?->code,
            ]],
            $model instanceof Student => ['student.created', [
                'code' => $model->code, 'full_name' => $model->full_name, 'pathway' => $model->pathway,
                'branch_code' => $model->branch?->code,
            ]],
            $model instanceof Certificate => ['certificate.issued', [
                'serial' => $model->serial, 'student_code' => $model->student?->code, 'title' => $model->title_en,
                'issued_at' => $model->issued_at?->toDateString(), 'verification_url' => $model->verificationUrl(),
            ]],
            $model instanceof Inquiry => ['inquiry.received', [
                'name' => $model->name, 'phone' => $model->phone, 'email' => $model->email, 'subject' => $model->subject,
            ]],
            $model instanceof SupportTicket => ['support.ticket_opened', [
                'number' => $model->number, 'subject' => $model->subject, 'category' => $model->category, 'priority' => $model->priority,
            ]],
            default => [null, null],
        };

        if ($event) {
            Webhooks::dispatch($event, $data);
        }
    }
}
