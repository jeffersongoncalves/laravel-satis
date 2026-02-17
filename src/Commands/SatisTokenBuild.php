<?php

namespace JeffersonGoncalves\LaravelSatis\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class SatisTokenBuild extends Command
{
    protected $signature = 'satis:token-build {--token= : Build for a specific token ID}';

    protected $description = 'Build Satis repository for token-specific packages';

    public function handle(): int
    {
        $tokenId = $this->option('token');
        $tokenModel = ModelResolver::token();

        if ($tokenId) {
            $token = $tokenModel::find($tokenId);

            if (! $token) {
                $this->components->error("Token [{$tokenId}] not found.");

                return self::FAILURE;
            }

            SyncTokenPackages::dispatch($token);
            $this->components->info("Dispatched build for token: {$tokenId}");

            return self::SUCCESS;
        }

        $tokens = $tokenModel::query()
            ->whereHas('packages')
            ->get();

        if ($tokens->isEmpty()) {
            $this->components->warn('No tokens with packages found.');

            return self::SUCCESS;
        }

        foreach ($tokens as $token) {
            SyncTokenPackages::dispatch($token);
        }

        $this->components->info("Dispatched build for {$tokens->count()} tokens.");

        return self::SUCCESS;
    }
}
