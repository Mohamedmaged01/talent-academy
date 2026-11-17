<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $values = collect(Setting::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => Setting::get($key)]);

        return view('admin.settings', compact('values'));
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(Setting::DEFAULTS)->mapWithKeys(fn ($v, $key) => [$key => ['nullable', 'string', 'max:500']])->all();
        $rules['contact_email'] = ['nullable', 'email'];
        $rules['map_embed_url'] = ['nullable', 'url', 'max:1000'];

        Setting::put($request->validate($rules));
        ActivityLog::record('settings.updated', 'Platform settings updated');

        return back()->with('success', __('Settings saved.'));
    }
}
