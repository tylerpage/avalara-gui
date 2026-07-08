<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasscodeService
{
    public const HASH_KEY = 'gui_passcode_hash';

    private const SESSION_KEY = 'gui_passcode_verified';

    public function isRequired(): bool
    {
        return filled($this->getHash()) || filled($this->envPasscode());
    }

    public function isAuthenticated(Request $request): bool
    {
        if (! $this->isRequired()) {
            return true;
        }

        return $request->session()->get(self::SESSION_KEY, false) === true;
    }

    public function verify(string $passcode): bool
    {
        if (! $this->isRequired()) {
            return true;
        }

        $hash = $this->getHash();

        if (filled($hash)) {
            return Hash::check($passcode, $hash);
        }

        $envPasscode = $this->envPasscode();

        return filled($envPasscode) && hash_equals($envPasscode, $passcode);
    }

    public function authenticate(Request $request, string $passcode): bool
    {
        if (! $this->verify($passcode)) {
            return false;
        }

        $request->session()->put(self::SESSION_KEY, true);

        return true;
    }

    public function logout(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function setPasscode(string $passcode): void
    {
        IntegrationSetting::set(self::HASH_KEY, Hash::make($passcode));
    }

    public function clearPasscode(): void
    {
        IntegrationSetting::query()->where('key', self::HASH_KEY)->delete();
    }

    public function hasStoredPasscode(): bool
    {
        return filled($this->getHash());
    }

    private function getHash(): ?string
    {
        $hash = IntegrationSetting::get(self::HASH_KEY);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function envPasscode(): ?string
    {
        $passcode = env('GUI_PASSCODE');

        return is_string($passcode) && trim($passcode) !== '' ? trim($passcode) : null;
    }
}
