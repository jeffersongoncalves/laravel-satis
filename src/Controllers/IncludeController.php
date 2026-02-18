<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class IncludeController extends Controller
{
    public function __invoke(Request $request, string $include): JsonResponse|Response
    {
        $token = $request->user(config('satis.auth.guard'));

        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = $this->getTenantPrefix($request, $token);
        $includeFile = $storagePath.'/'.$tenantPrefix.$token->id.'/include/'.$include.'.json';

        if (! $disk->exists($includeFile)) {
            return response()->noContent(404);
        }

        $content = json_decode($disk->get($includeFile), true);

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
