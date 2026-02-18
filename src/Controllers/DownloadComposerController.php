<?php

namespace JeffersonGoncalves\LaravelSatis\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JeffersonGoncalves\LaravelSatis\Jobs\DownloadComposerJob;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;

class DownloadComposerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $downloads = $request->get('downloads', []);
        $packageModel = ModelResolver::package();

        foreach ($downloads as $download) {
            $package = $packageModel::where('name', $download['name'] ?? null)->first();

            if ($package) {
                DownloadComposerJob::dispatch($package->id, $download['version'] ?? '*');
            }
        }

        return response()->json(status: 201, options: JSON_UNESCAPED_UNICODE);
    }
}
