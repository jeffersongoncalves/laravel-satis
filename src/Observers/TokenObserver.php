<?php

namespace JeffersonGoncalves\LaravelSatis\Observers;

use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Token;

class TokenObserver
{
    public function creating(Token $token): void
    {
        if (empty($token->token)) {
            $token->token = $token::generateCode('token');
        }
    }

    public function created(Token $token): void
    {
        SyncTokenPackages::dispatch($token);
    }

    public function deleted(Token $token): void
    {
        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = '';
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $token->{$fk} ?? null;
            if ($tenantId) {
                $tenantPrefix = $tenantId.'/';
            }
        }

        $tokenPath = $storagePath.'/'.$tenantPrefix.$token->id;

        if ($disk->exists($tokenPath)) {
            $disk->deleteDirectory($tokenPath);
        }
    }
}
