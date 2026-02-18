<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PackagesV2Controller extends Controller
{
    public function __invoke(Request $request, string $vendor, string $package): JsonResponse|Response
    {
        $token = $request->user(config('satis.auth.guard'));

        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = $this->getTenantPrefix($request, $token);
        $packageFile = $storagePath.'/'.$tenantPrefix.$token->id.'/p2/'.$vendor.'/'.$package.'.json';

        if (! $disk->exists($packageFile)) {
            return response()->noContent(404);
        }

        $content = json_decode($disk->get($packageFile), true);

        return response()->json($content);
    }

    protected function getTenantPrefix(Request $request, $token): string
    {
        if (! config('satis.tenancy.enabled')) {
            return '';
        }

        $tenantId = $request->route('tenant');

        if ($tenantId) {
            return $tenantId.'/';
        }

        $fk = config('satis.tenancy.foreign_key');
        $tenantId = $token->{$fk} ?? null;

        return $tenantId ? $tenantId.'/' : '';
    }
}
