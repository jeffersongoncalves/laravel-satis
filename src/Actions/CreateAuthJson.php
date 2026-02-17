<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;

class CreateAuthJson
{
    /**
     * Create an auth.json file in the composer home directory
     * for HTTP Basic authentication with Composer-type packages.
     */
    public function execute(Collection $packages, ?string $composerHomePath = null): string
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');
        $composerHome = $composerHomePath ?? $storagePath.'/composer';

        $authJson = ['http-basic' => []];

        $packages
            ->filter(fn ($package) => $package->type === PackageType::Composer)
            ->each(function ($package) use (&$authJson) {
                $host = parse_url($package->url, PHP_URL_HOST);

                if ($host && $package->username && $package->password) {
                    $authJson['http-basic'][$host] = [
                        'username' => $package->username,
                        'password' => $package->password,
                    ];
                }
            });

        $authJsonPath = $composerHome.'/auth.json';

        $disk->put(
            $authJsonPath,
            json_encode($authJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $disk->path($composerHome);
    }
}
