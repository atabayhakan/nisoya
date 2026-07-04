<?php

namespace App\Http\Controllers;

use App\Services\NabizService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NabizController extends Controller
{
    public function index(Request $request, NabizService $nabiz): View
    {
        $storyWallEnabled = $nabiz->storyWallEnabled();

        return view('nabiz', [
            'goal' => $nabiz->goalProgress(),
            'ambassadors' => $nabiz->cityAmbassadors(20),
            'storyWallEnabled' => $storyWallEnabled,
            'stories' => $storyWallEnabled ? $nabiz->approvedStories() : collect(),
            'myStoryStatus' => $storyWallEnabled && $request->user()
                ? $nabiz->latestStoryStatusFor($request->user())
                : null,
        ]);
    }
}
