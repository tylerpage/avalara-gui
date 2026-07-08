<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\DashboardContext;
use App\Services\PasscodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceDashboardController extends Controller
{
    public function index(DashboardContext $dashboards): Response
    {
        return Inertia::render('Dashboards/Index', [
            'dashboards' => $dashboards->all()->map(fn (Dashboard $dashboard) => $this->serialize($dashboard))->values(),
            'currentDashboardId' => $dashboards->id(),
        ]);
    }

    public function store(Request $request, DashboardContext $dashboards): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $dashboard = $dashboards->create($validated['name']);

        $dashboards->switch($dashboard);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Dashboard "'.$dashboard->name.'" created and selected.');
    }

    public function switch(Dashboard $dashboard, DashboardContext $dashboards, PasscodeService $passcode): RedirectResponse
    {
        $dashboards->switch($dashboard);

        if ($passcode->isRequired()) {
            $passcode->logout(request());

            return redirect()
                ->route('passcode.show')
                ->with('success', 'Switched to "'.$dashboard->name.'". Enter its passcode to continue.');
        }

        return redirect()
            ->back()
            ->with('success', 'Switched to "'.$dashboard->name.'".');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Dashboard $dashboard): array
    {
        return [
            'id' => $dashboard->id,
            'name' => $dashboard->name,
            'slug' => $dashboard->slug,
            'webhookUrl' => $dashboard->webhookUrl(),
        ];
    }
}
