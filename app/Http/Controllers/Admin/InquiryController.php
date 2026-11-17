<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.inquiries.index', [
            'inquiries' => Inquiry::when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update($request->validate(['status' => ['required', Rule::in(['new', 'read', 'archived'])]]));

        return back()->with('success', __('Message updated.'));
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return back()->with('success', __('Message deleted.'));
    }
}
