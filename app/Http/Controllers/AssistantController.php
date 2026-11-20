<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/** Chat page for the assistant (administrators and teachers). History lives in the session only. */
class AssistantController extends Controller
{
    public function __construct(private AssistantService $assistant) {}

    public function index(Request $request): View
    {
        return view('assistant.index', [
            'history' => $request->session()->get($this->key($request), []),
            'configured' => $this->assistant->configured(),
            'prefix' => $request->user()->role,
            'suggestions' => $request->user()->role === 'teacher'
                ? [__('Which of my classes needs the most attention this week?'), __('Suggest a 45-minute lesson plan for my next session.'), __('Write a polite reminder to students about a missing assignment.')]
                : [__('Summarise the academy\'s situation this month.'), __('How can we reduce overdue fees?'), __('Draft an announcement about the next term\'s registration.')],
        ]);
    }

    public function ask(Request $request): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $key = $this->key($request);
        $history = $request->session()->get($key, []);

        try {
            $answer = $this->assistant->ask($request->user(), $history, $data['message']);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $history[] = ['role' => 'user', 'text' => $data['message']];
        $history[] = ['role' => 'model', 'text' => $answer];
        $request->session()->put($key, array_slice($history, -20));

        return redirect()->to(route($request->user()->role.'.assistant').'#latest');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget($this->key($request));

        return back();
    }

    private function key(Request $request): string
    {
        return 'assistant.history.'.$request->user()->id;
    }
}
