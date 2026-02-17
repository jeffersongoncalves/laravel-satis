<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class GithubWebhookController extends Controller
{
    public function handle(Request $request, Package $package): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256');

        if ($package->webhook_secret && $signature) {
            $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $package->webhook_secret);

            if (! hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $tenantId = null;
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $package->{$fk} ?? null;
        }

        SyncTenantPackages::dispatch($tenantId);

        return response()->json(['status' => 'ok']);
    }
}
