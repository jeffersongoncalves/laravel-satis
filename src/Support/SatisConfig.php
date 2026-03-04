<?php

namespace JeffersonGoncalves\LaravelSatis\Support;

use Illuminate\Support\Collection;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class SatisConfig
{
    protected array $config;

    protected Collection $packages;

    protected ?string $homepage = null;

    protected ?string $notifyBatch = null;

    public function __construct()
    {
        $this->config = config('satis.satis', []);
        $this->packages = collect();
    }

    public static function make(): static
    {
        return new static;
    }

    public function setPackages(Collection $packages): static
    {
        $this->packages = $packages;

        return $this;
    }

    public function setHomepage(string $homepage): static
    {
        $this->homepage = $homepage;

        return $this;
    }

    public function setNotifyBatch(string $url): static
    {
        $this->notifyBatch = $url;

        return $this;
    }

    public function toArray(): array
    {
        $config = $this->normalizeKeys($this->config);
        $config['homepage'] = $this->homepage ?? url('/');
        $config['notify-batch'] = $this->notifyBatch ?? route('composer.downloads');
        $config['repositories'] = $this->buildRepositories();
        $config['require'] = $this->buildRequires();

        return $config;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function buildRepositories(): array
    {
        // Track credentials per URL to make duplicate URLs with different
        // credentials unique. Composer caches HTTP responses and deduplicates
        // repositories by URL, so packages on the same URL with different
        // credentials would all use the first credential's cached response.
        $urlCredentials = [];
        $seen = [];

        return $this->packages->map(function (Package $package) use (&$urlCredentials, &$seen) {
            $type = $this->resolveRepositoryType($package);
            $url = $package->url;

            $repo = [
                'type' => $type,
                'url' => $url,
            ];

            if ($package->username && $package->password) {
                $normalizedUrl = rtrim($url, '/');
                $mapKey = $type.':'.$normalizedUrl;
                $credentialHash = md5($package->username.':'.$package->password);

                if (! isset($urlCredentials[$mapKey])) {
                    $urlCredentials[$mapKey] = [];
                }

                if (! isset($urlCredentials[$mapKey][$credentialHash])) {
                    $urlCredentials[$mapKey][$credentialHash] = count($urlCredentials[$mapKey]);
                }

                $suffixIndex = $urlCredentials[$mapKey][$credentialHash];

                if ($suffixIndex > 0) {
                    $repo['url'] = $normalizedUrl.str_repeat('/.', $suffixIndex);
                }

                $repo['options'] = [
                    'http' => [
                        'header' => [
                            'Authorization: Basic '.base64_encode($package->username.':'.$package->password),
                        ],
                    ],
                ];
            }

            // Deduplicate: same URL + same credentials only needs one repo entry.
            $dedupeKey = $type.':'.$repo['url'];
            if (isset($seen[$dedupeKey])) {
                return null;
            }
            $seen[$dedupeKey] = true;

            return $repo;
        })->filter()->values()->toArray();
    }

    protected function buildRequires(): array
    {
        return $this->packages->mapWithKeys(function (Package $package) {
            return [$package->name => '*'];
        })->toArray();
    }

    protected function normalizeKeys(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? str_replace('_', '-', $key) : $key;
            $normalized[$normalizedKey] = is_array($value) ? $this->normalizeKeys($value) : $value;
        }

        return $normalized;
    }

    protected function resolveRepositoryType(Package $package): string
    {
        return match ($package->type) {
            PackageType::Github => 'vcs',
            default => 'composer',
        };
    }
}
