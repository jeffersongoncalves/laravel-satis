<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class PackagesV2Controller extends Controller
{
    public function show(Request $request, string $vendor, string $package): JsonResponse
    {
        $token = $request->attributes->get('satis_token');

        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $disk = Storage::disk(config('laravel-satis.storage_disk'));
        $storagePath = config('laravel-satis.storage_path', 'satis');

        $tenantPrefix = $this->getTenantPrefix($request, $token);
        $buildPath = $storagePath.'/'.$tenantPrefix.$token->id;
        $packageFile = $buildPath.'/p2/'.$vendor.'/'.$package.'.json';

        if (! $disk->exists($packageFile)) {
            return response()->json(['error' => 'Package not found'], 404);
        }

        $content = json_decode($disk->get($packageFile), true);

        return response()->json($content);
    }

    protected function getTenantPrefix(Request $request, $token): string
    {
        if (! config('laravel-satis.tenancy.enabled')) {
            return '';
        }

        $tenantId = $request->route('tenant');

        if ($tenantId) {
            return $tenantId.'/';
        }

        $fk = config('laravel-satis.tenancy.foreign_key');
        $tenantId = $token->{$fk} ?? null;

        return $tenantId ? $tenantId.'/' : '';
    }
}
