<?php

namespace App\Services;

use App\Models\Dashboard;
use Illuminate\Support\Collection;

class DashboardContext
{
    private const SESSION_KEY = 'current_dashboard_id';

    private ?Dashboard $override = null;

    public function current(): Dashboard
    {
        if ($this->override !== null) {
            return $this->override;
        }

        $dashboardId = session(self::SESSION_KEY);

        if (is_numeric($dashboardId)) {
            $dashboard = Dashboard::query()->find((int) $dashboardId);

            if ($dashboard !== null) {
                return $dashboard;
            }
        }

        $dashboard = $this->ensureDefault();
        $this->switch($dashboard);

        return $dashboard;
    }

    public function id(): int
    {
        return $this->current()->id;
    }

    public function slug(): string
    {
        return $this->current()->slug;
    }

    public function setCurrent(Dashboard $dashboard): void
    {
        $this->override = $dashboard;
    }

    public function switch(Dashboard $dashboard): void
    {
        session([self::SESSION_KEY => $dashboard->id]);
        $this->override = null;
    }

    /**
     * @return Collection<int, Dashboard>
     */
    public function all(): Collection
    {
        return Dashboard::query()->orderBy('name')->get();
    }

    public function create(string $name): Dashboard
    {
        return Dashboard::query()->create([
            'name' => trim($name),
            'slug' => Dashboard::uniqueSlug($name),
        ]);
    }

    public function ensureDefault(): Dashboard
    {
        $existing = Dashboard::query()->orderBy('id')->first();

        if ($existing !== null) {
            return $existing;
        }

        return Dashboard::query()->create([
            'name' => 'Default',
            'slug' => 'default',
        ]);
    }
}
