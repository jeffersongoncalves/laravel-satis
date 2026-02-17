<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTenantPackages;
use JeffersonGoncalves\LaravelSatis\Jobs\SyncTokenPackages;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use Symfony\Component\HttpFoundation\Response;

class GithubWebhookController extends Controller
{
    public function handle(Request $request, Package $package): JsonResponse
    {
        if ($package->type !== PackageType::Github) {
            return response()->json(['error' => 'Package is not a GitHub package'], Response::HTTP_BAD_REQUEST);
        }

        $event = $request->header('X-GitHub-Event');

        if (! in_array($event, ['release', 'push', 'create'])) {
            return response()->json(['message' => 'Event ignored'], Response::HTTP_OK);
        }

        if (! $this->verifySignature($request, $package->webhook_secret)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $tenantId = null;
        if (config('satis.tenancy.enabled')) {
            $fk = config('satis.tenancy.foreign_key');
            $tenantId = $package->{$fk} ?? null;
        }

        SyncTenantPackages::dispatch($tenantId);

        $package->tokens()->each(function ($token) {
            SyncTokenPackages::dispatch($token);
        });

        return response()->json(['status' => 'ok']);
    }

    protected function verifySignature(Request $request, ?string $secret): bool
    {
        if (blank($secret)) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if ($signature === null) {
            return true;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
