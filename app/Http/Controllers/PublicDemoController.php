<?php

namespace App\Http\Controllers;

use App\Support\PublicDemoWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PublicDemoController extends Controller
{
    public function __invoke(PublicDemoWorkspace $demo): RedirectResponse
    {
        abort_unless($demo->enabled(), 404);

        $user = $demo->ensure();

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Public demo changes are temporary. This workspace resets every 30 minutes.');
    }
}
