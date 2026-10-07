<?php

namespace App\Http\Controllers;

use App\Models\VirtualTestAttempt;
use App\Models\VirtualTestTemplate;
use App\Services\VirtualTestService;
use Illuminate\Http\Request;

class VirtualTestController extends Controller
{
    private function owner(Request $request): string
    {
        // The random owner token survives session-id regeneration after admin login.
        $token = $request->session()->get('virtual_test_owner');
        if (! $token) {
            $token = bin2hex(random_bytes(32));
            $request->session()->put('virtual_test_owner', $token);
        }

        return hash('sha256', $token);
    }

    public function start(Request $request, VirtualTestTemplate $template, VirtualTestService $service)
    {
        return response()->json($service->payload($service->start($template, $this->owner($request))))->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, VirtualTestAttempt $attempt, VirtualTestService $service)
    {
        return response()->json($service->payload($service->mutate($attempt, $this->owner($request), [])))->header('Cache-Control', 'no-store');
    }

    public function save(Request $request, VirtualTestAttempt $attempt, VirtualTestService $service)
    {
        $data = $request->validate(['answers' => 'required|array|max:200', 'answers.*' => 'integer|min:0|max:3']);
        $answers = array_map(fn ($a) => (int) $a, $data['answers']);

        return response()->json($service->payload($service->mutate($attempt, $this->owner($request), $answers)))->header('Cache-Control', 'no-store');
    }

    public function finish(Request $request, VirtualTestAttempt $attempt, VirtualTestService $service)
    {
        $data = $request->validate(['answers' => 'present|array|max:200', 'answers.*' => 'integer|min:0|max:3']);
        $answers = array_map(fn ($a) => (int) $a, $data['answers']);

        return response()->json($service->payload($service->mutate($attempt, $this->owner($request), $answers, true)))->header('Cache-Control', 'no-store');
    }
}
