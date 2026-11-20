<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('contact', [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120', 'required_without:phone'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $inquiry = Inquiry::create($data + ['status' => 'new']);
        Notify::admins('New contact message from :name', ['name' => $inquiry->name], route('admin.inquiries.index'), 'inbox', 'communication');

        return redirect()->to(route('home').'#contact')
            ->with('contact_success', __('Thank you! Your message has been received and our team will contact you soon.'));
    }
}
