<?php

namespace JeffersonGoncalves\LaravelSatis\Support;

use Illuminate\Support\Collection;
use JeffersonGoncalves\LaravelSatis\Data\PackageData;
use JeffersonGoncalves\LaravelSatis\Data\RepositoryData;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use Stringable;

class SatisConfig implements Stringable
{
    protected array $config = [];

    protected array $repositories = [];

    protected array $requires = [];

    protected array $httpBasic = [];

    protected ?string $homepage = null;

    protected ?string $notifyBatch = null;

    protected ?string $outputDir = null;

    public ?string $path = null;

    protected function __construct(?string $path = null)
    {
        $this->config = config('satis.satis', []);
        $this->path = $path;
    }

    public static function make(): static
    {
        return new static;
    }

    public function homepage(string $url): static
    {
        $this->homepage = $url;

        return $this;
    }

    public function notifyBatch(string $url): static
    {
        $this->notifyBatch = $url;

        return $this;
    }

    public function outputDir(string $dir): static
    {
        $this->outputDir = $dir;

        return $this;
    }

    public function httpBasic(string $host, string $username, string $password): static
    {
        $this->httpBasic[$host] = [
            'username' => $username,
            'password' => $password,
        ];

        return $this;
    }

    public function repository(RepositoryData $repository): static
    {
        $this->repositories[] = $repository;

        return $this;
    }

    public function require(PackageData $package): static
    {
        $this->requires[$package->name] = $package->version;

        return $this;
    }

    /**
     * Build config from a collection of Package models (convenience method).
     *
     * Handles credential grouping, conflict detection, and deduplication
     * for building satis.json repositories and requires.
     */
    public function setPackages(Collection $packages): static
    {
        $conflictedHosts = $this->detectConflictedHosts($packages);
        $urlCredentials = [];
        $seen = [];

        foreach ($packages as $package) {
            $this->requires[$package->name] = '*';

            $type = $this->resolveRepositoryType($package);
            $credential = $package->credential;
            $url = $credential->url ?? '';

            $repo = [
                'type' => $type,
                'url' => $url,
            ];

            if ($credential && $credential->email && $credential->password) {
                $host = parse_url($url, PHP_URL_HOST);
                $isVcs = $type !== 'composer';
                $hasConflict = isset($conflictedHosts[$host]);

                if ($isVcs || $hasConflict) {
                    if ($hasConflict) {
                        $normalizedUrl = rtrim($url, '/');
                        $mapKey = $type.':'.$normalizedUrl;
                        $credentialHash = md5($credential->email.':'.$credential->password);

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
                                'Authorization: Basic '.base64_encode($credential->email.':'.$credential->password),
                            ],
                        ],
                    ];
                }
            }

            $dedupeKey = $type.':'.$repo['url'];
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $this->repositories[] = $repo;
        }

        return $this;
    }

    /**
     * Set homepage from string (BC convenience).
     */
    public function setHomepage(string $homepage): static
    {
        return $this->homepage($homepage);
    }

    /**
     * Set notify-batch URL (BC convenience).
     */
    public function setNotifyBatch(string $url): static
    {
        return $this->notifyBatch($url);
    }

    public function toArray(): array
    {
        $config = $this->normalizeKeys($this->config);
        $config['homepage'] = $this->homepage ?? url('/');
        $config['notify-batch'] = $this->notifyBatch ?? route('composer.downloads');

        $config['repositories'] = $this->buildRepositories();
        $config['require'] = $this->requires;

        if (! empty($this->httpBasic)) {
            $config['config']['http-basic'] = $this->httpBasic;
        }

        if ($this->outputDir) {
            $config['output-dir'] = $this->outputDir;
        }

        return $config;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    protected function buildRepositories(): array
    {
        $repos = [];

        foreach ($this->repositories as $repo) {
            if ($repo instanceof RepositoryData) {
                $repos[] = $repo->toArray();
            } else {
                $repos[] = $repo;
            }
        }

        return $repos;
    }

    /**
     * Detect Composer hostnames that have multiple different credential sets.
     *
     * @return array<string, true>
     */
    protected function detectConflictedHosts(Collection $packages): array
    {
        $hostCredentials = [];

        foreach ($packages as $package) {
            $credential = $package->credential;

            if ($package->type === PackageType::Composer && $credential && $credential->email && $credential->password) {
                $host = parse_url($credential->url, PHP_URL_HOST);
                if ($host) {
                    $hostCredentials[$host][md5($credential->email.':'.$credential->password)] = true;
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
