<?php

namespace JeffersonGoncalves\LaravelSatis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Actions\MergeSatisPackagesJson;
use JeffersonGoncalves\LaravelSatis\Actions\SanitizeSatisPackages;
use JeffersonGoncalves\LaravelSatis\Data\PackageData;
use JeffersonGoncalves\LaravelSatis\Data\RepositoryData;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Support\SatisConfig;

class SyncTokenPackages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_SECONDS = 30;

    private const SAME_HOST_WAIT_SECONDS = 5;

    public function __construct(
        protected Token $token
    ) {
        $queueConfig = config('satis.queue');

        $this->timeout = $queueConfig['timeout'] ?? 86400;

        if ($queueConfig['connection'] ?? null) {
            $this->onConnection($queueConfig['connection']);
        }

        if ($queueConfig['queue_name'] ?? null) {
            $this->onQueue($queueConfig['queue_name']);
        }
    }

    public function handle(): void
    {
        /** @var Collection<int, Package> $packages */
        $packages = $this->token->packages()->with('credential')->get();

        if ($packages->isEmpty()) {
            return;
        }

        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = '';
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $this->token->{$fk} ?? null;
            if ($tenantId) {
                $tenantPrefix = $tenantId.'/';
            }
        }

        $buildPath = $storagePath.'/'.$tenantPrefix.$this->token->id;
        $composerHomePath = $storagePath.'/'.$tenantPrefix.'composer';

        $routeParams = [];
        if (config('satis.tenancy.enabled') && $tenantPrefix) {
            $routeParams['tenant'] = rtrim($tenantPrefix, '/');
        }

        $builds = $this->groupBuilds($packages);
        $snapshots = [];
        $previousHosts = [];

        foreach ($builds as $index => $build) {
            $this->waitIfSameHost($build['host'], $previousHosts);

            $satisConfig = SatisConfig::make()
                ->setHomepage(url(config('satis.routes.composer_prefix') ?? '/'))
                ->setNotifyBatch(route('composer.downloads', $routeParams));

            foreach ($build['packages'] as $package) {
                $this->addPackageToConfig($satisConfig, $package);
            }

            $snapshotDir = $buildPath.'/snapshot_'.$index;
            $configPath = $snapshotDir.'/satis.json';
            $disk->put($configPath, $satisConfig->toJson());

            $satisBinary = config('satis.satis_binary') ?? base_path('vendor/bin/satis');
            $fullConfigPath = $disk->path($configPath);
            $fullSnapshotDir = $disk->path($snapshotDir);
            $fullComposerHome = $disk->path($composerHomePath);

            if (! $disk->exists($composerHomePath)) {
                $disk->makeDirectory($composerHomePath);
            }

            $this->writeAuthJson($disk, $composerHomePath, $build);

            $success = $this->runSatisBuildWithRetry(
                $satisBinary, $fullConfigPath, $fullSnapshotDir, $fullComposerHome
            );

            $disk->delete($configPath);

            $snapshotPackagesJson = $snapshotDir.'/packages.json';
            if ($success && $disk->exists($snapshotPackagesJson)) {
                app(SanitizeSatisPackages::class)->sanitizeDirectory($snapshotDir, $disk);
                $snapshots[] = $snapshotPackagesJson;
            }

            if ($build['host']) {
                $previousHosts[] = $build['host'];
            }
        }

        if (! empty($snapshots)) {
            app(MergeSatisPackagesJson::class)->handle($buildPath, $snapshots, $disk);

            $packages->each(function (Package $package) {
                ProcessPackageDependency::dispatch($package);
            });
        }
    }

    /**
     * Group packages by credential for separate builds.
     */
    protected function groupBuilds(Collection $packages): array
    {
        $groups = [];

        foreach ($packages as $package) {
            $credential = $package->credential;
            $key = $credential ? $credential->id : 'none';

            if (! isset($groups[$key])) {
                $host = $credential ? parse_url($credential->url, PHP_URL_HOST) : null;
                $groups[$key] = [
                    'credential' => $credential,
                    'host' => $host,
                    'packages' => [],
                ];
            }

            $groups[$key]['packages'][] = $package;
        }

        return array_values($groups);
    }

    protected function waitIfSameHost(?string $host, array $previousHosts): void
    {
        if ($host && in_array($host, $previousHosts)) {
            sleep(self::SAME_HOST_WAIT_SECONDS);
        }
    }

    protected function addPackageToConfig(SatisConfig $config, Package $package): void
    {
        $credential = $package->credential;

        if (! $credential) {
            return;
        }

        $url = $this->buildInlineAuthUrl($package);
        $type = $package->type === PackageType::Github ? 'vcs' : 'composer';

        $config->repository(new RepositoryData(
            name: $package->name,
            type: $type,
            url: $url,
        ));

        $config->require(new PackageData(
            name: $package->name,
        ));
    }

    protected function buildInlineAuthUrl(Package $package): string
    {
        $credential = $package->credential;

        if (! $credential || ! $credential->email || ! $credential->password) {
            return $credential->url ?? '';
        }

        $url = $credential->url;
        $parsed = parse_url($url);
        $scheme = ($parsed['scheme'] ?? 'https').'://';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        $encodedUser = rawurlencode($credential->email);
        $encodedPass = rawurlencode($credential->password);

        return $scheme.$encodedUser.':'.$encodedPass.'@'.$host.$port.$path.$query;
    }

    protected function writeAuthJson($disk, string $composerHomePath, array $build): void
    {
        $authJson = ['http-basic' => []];

        $credential = $build['credential'] ?? null;

        if ($credential && $credential->email && $credential->password) {
            $host = parse_url($credential->url, PHP_URL_HOST);
            if ($host) {
                $authJson['http-basic'][$host] = [
                    'username' => $credential->email,
                    'password' => $credential->password,
                ];
            }
        }

        $disk->put(
            $composerHomePath.'/auth.json',
            json_encode($authJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function runSatisBuildWithRetry(
        string $satisBinary,
        string $configPath,
        string $outputDir,
        string $composerHome,
    ): bool {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $result = Process::timeout($this->timeout)
                ->env(['COMPOSER_HOME' => $composerHome])
                ->run([
                    'php', $satisBinary, 'build', $configPath, $outputDir, '--skip-errors',
                ]);

            if ($result->successful()) {
                return true;
            }

            $output = $result->errorOutput().$result->output();

            if ($this->isRateLimited($output) && $attempt < self::MAX_RETRIES) {
                $delay = self::RETRY_DELAY_SECONDS * $attempt;

                Log::warning('Satis build rate-limited, retrying', [
                    'attempt' => $attempt,
                    'delay' => $delay,
                    'token_id' => $this->token->id,
                ]);

                sleep($delay);

                continue;
            }

            Log::error('Satis token build failed', [
                'token_id' => $this->token->id,
                'attempt' => $attempt,
                'output' => $this->sanitizeOutput($output),
            ]);

            break;
        }

        return false;
    }

    protected function isRateLimited(string $output): bool
    {
        return str_contains($output, '429')
            || stripos($output, 'rate limit') !== false
            || stripos($output, 'too many requests') !== false;
    }

    protected function sanitizeOutput(string $output): string
    {
        return preg_replace('#(https?://)([^@/:]+:[^@/:]+)@#', '$1***:***@', $output) ?? $output;
    }
}
