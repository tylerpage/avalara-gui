<?php

namespace App\Http\Controllers;

use App\Services\PasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasscodeController extends Controller
{
    public function show(Request $request, PasscodeService $passcode): Response|RedirectResponse
    {
        if (! $passcode->isRequired() || $passcode->isAuthenticated($request)) {
            return redirect()->intended(route('dashboard'));
        }

        return Inertia::render('Passcode/Enter');
    }

    public function store(Request $request, PasscodeService $passcode): RedirectResponse
    {
        $validated = $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        if (! $passcode->authenticate($request, $validated['passcode'])) {
            return back()->withErrors([
                'passcode' => 'Incorrect passcode.',
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, PasscodeService $passcode): RedirectResponse
    {
        $passcode->logout($request);

        return redirect()->route('passcode.show');
    }
}
