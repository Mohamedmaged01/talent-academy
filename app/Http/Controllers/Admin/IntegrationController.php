<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Support\Webhooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Outgoing webhooks: send platform events to other systems (accounting, CRM, WhatsApp gateways, Zapier…). */
class IntegrationController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index', [
            'webhooks' => Webhook::withCount(['deliveries', 'deliveries as failed_count' => fn ($q) => $q->where('success', false)])->orderBy('name')->get(),
            'deliveries' => WebhookDelivery::with('webhook')->latest('created_at')->latest('id')->take(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $webhook = Webhook::create($this->validated($request) + ['secret' => Str::random(40), 'is_active' => true]);
        ActivityLog::record('webhook.created', "Webhook \"{$webhook->name}\" added for {$webhook->url}", $webhook);

        return back()->with('success', __('Webhook added. Its signing secret is shown in its card.'));
    }

    public function update(Request $request, Webhook $webhook): RedirectResponse
    {
        $webhook->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', __('Webhook updated.'));
    }

    public function rotate(Webhook $webhook): RedirectResponse
    {
        $webhook->update(['secret' => Str::random(40)]);
        ActivityLog::record('webhook.rotated', "Signing secret rotated for webhook \"{$webhook->name}\"", $webhook);

        return back()->with('success', __('New signing secret generated. Update the receiving system.'));
    }

    public function test(Webhook $webhook): RedirectResponse
    {
        $delivery = Webhooks::deliver($webhook, 'ping', Webhooks::payload('ping', ['message' => 'Test delivery from Talent Academy']));

        return back()->with($delivery->success ? 'success' : 'error', $delivery->success
            ? __('Test delivered (HTTP :status in :ms ms).', ['status' => $delivery->status_code, 'ms' => $delivery->duration_ms])
            : __('Test failed: :reason', ['reason' => $delivery->status_code ? 'HTTP '.$delivery->status_code : $delivery->response_excerpt]));
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        ActivityLog::record('webhook.deleted', "Webhook \"{$webhook->name}\" deleted");
        $webhook->delete();

        return back()->with('success', __('Webhook deleted.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url:https,http', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(array_keys(Webhook::EVENTS))],
        ]);
    }
}
