<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class ValidatePackageCredentials
{
    public function execute(Package $package): bool
    {
        $credential = $package->credential;

        if (! $credential) {
            $package->update([
                'is_credentials_validated' => false,
                'credentials_validated_at' => null,
            ]);

            return false;
        }

        $isValid = match ($package->type) {
            PackageType::Composer => $this->validateComposer($credential),
            PackageType::Github => $this->validateGithub($credential),
            default => false,
        };

        $package->update([
            'is_credentials_validated' => $isValid,
            'credentials_validated_at' => $isValid ? now() : null,
        ]);

        return $isValid;
    }

    protected function validateComposer($credential): bool
    {
        try {
            $response = Http::withBasicAuth(
                $credential->email ?? '',
                $credential->password ?? ''
            )->get($credential->url.'/packages.json');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function validateGithub($credential): bool
    {
        try {
            $url = $credential->url;

            if ($credential->email && $credential->password) {
                $parsed = parse_url($url);
                $url = ($parsed['scheme'] ?? 'https').'://'
                    .$credential->email.':'.$credential->password.'@'
                    .($parsed['host'] ?? '')
                    .($parsed['path'] ?? '');
            }

            $result = Process::timeout(30)->run(['git', 'ls-remote', $url]);

            return $result->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
