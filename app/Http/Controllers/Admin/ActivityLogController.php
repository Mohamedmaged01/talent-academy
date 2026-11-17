<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.activity', [
            'logs' => ActivityLog::with('user')
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                    ->where('description', 'like', "%{$term}%")->orWhere('action', 'like', "%{$term}%")))
                ->latest('created_at')->latest('id')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
