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
        // Detect Composer hostnames with credential conflicts (matching CreateAuthJson logic).
        // When all Composer packages on a hostname share the same credentials,
        // auth.json handles authentication via http-basic. When credentials differ,
        // auth.json skips the host and we use per-repository Authorization headers.
        $conflictedHosts = $this->detectConflictedHosts();

        $urlCredentials = [];
        $seen = [];

        return $this->packages->map(function (Package $package) use (&$urlCredentials, &$seen, $conflictedHosts) {
            $type = $this->resolveRepositoryType($package);
            $url = $package->url;

            $repo = [
                'type' => $type,
                'url' => $url,
            ];

            if ($package->username && $package->password) {
                // VCS/GitHub packages always need Authorization headers since
                // auth.json http-basic only covers Composer-type packages.
                // Composer packages only need headers when the hostname has
                // credential conflicts (auth.json skips conflicted hosts).
                $host = parse_url($url, PHP_URL_HOST);
                $isVcs = $type !== 'composer';
                $hasConflict = isset($conflictedHosts[$host]);

                if ($isVcs || $hasConflict) {
                    if ($hasConflict) {
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
                    }

                    $repo['options'] = [
                        'http' => [
                            'header' => [
                                'Authorization: Basic '.base64_encode($package->username.':'.$package->password),
                            ],
                        ],
                    ];
                }
            }

            // Deduplicate: same URL + same type only needs one repo entry.
            $dedupeKey = $type.':'.$repo['url'];
            if (isset($seen[$dedupeKey])) {
                return null;
            }
            $seen[$dedupeKey] = true;

            return $repo;
        })->filter()->values()->toArray();
    }

    /**
     * Detect Composer hostnames that have multiple different credential sets.
     *
     * @return array<string, true>
     */
    protected function detectConflictedHosts(): array
    {
        $hostCredentials = [];

        foreach ($this->packages as $package) {
            if ($package->type === PackageType::Composer && $package->username && $package->password) {
                $host = parse_url($package->url, PHP_URL_HOST);
                if ($host) {
                    $hostCredentials[$host][md5($package->username.':'.$package->password)] = true;
                }
            }
        }

        $conflicted = [];

        foreach ($hostCredentials as $host => $creds) {
            if (count($creds) > 1) {
                $conflicted[$host] = true;
            }
        }

        return $conflicted;
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
