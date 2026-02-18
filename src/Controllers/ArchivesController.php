<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ArchivesController extends Controller
{
    public function __invoke(Request $request, string $vendor, string $package, string $file): BinaryFileResponse|Response
    {
        $token = $request->user(config('satis.auth.guard'));

        $disk = Storage::disk(config('satis.storage_disk'));
        $storagePath = config('satis.storage_path', 'satis');

        $tenantPrefix = $this->getTenantPrefix($request, $token);
        $archivePath = $storagePath.'/'.$tenantPrefix.$token->id.'/archives/'.$vendor.'/'.$package.'/'.$file;

        if (! $disk->exists($archivePath)) {
            return response()->noContent(404);
        }

        return response()->file($disk->path($archivePath));
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
