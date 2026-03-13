<?php

namespace JeffersonGoncalves\LaravelSatis\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;
use JeffersonGoncalves\LaravelSatis\Models\Token;
use JeffersonGoncalves\LaravelSatis\Support\ModelResolver;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Token $token */
        $token = $request->user(config('satis.auth.guard'));
        $vendor = $request->route('vendor');
        $package = $request->route('package');
        $name = $vendor.'/'.$package;

        $packageModel = ModelResolver::package();

        if (! $packageModel::where('name', $name)->exists()) {
            abort(404);
        }

        $dependencyModel = ModelResolver::dependency();

        if (! $dependencyModel::where('type', DependencyType::Private)->where('name', $name)->exists()) {
            if (! $token->packages()->where('name', $name)->exists()) {
                abort(403);
            }
        }

        return $next($request);
    }
}
