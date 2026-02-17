<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelSatis\Actions\ProcessPackageDependency;
use JeffersonGoncalves\LaravelSatis\Jobs\AddDependencyDefaultByPackage;
use JeffersonGoncalves\LaravelSatis\Models\Dependency;
use JeffersonGoncalves\LaravelSatis\Models\Package;
use JeffersonGoncalves\LaravelSatis\Models\PackageRelease;

it('creates dependencies from requires list', function () {
    Http::fake([
        'repo.packagist.org/*' => Http::response(['packages' => []], 200),
    ]);

    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    $action = new ProcessPackageDependency;
    $action->execute($release, [
        'illuminate/support' => '^10.0',
        'illuminate/database' => '^10.0',
    ]);

    expect(Dependency::count())->toBe(2)
        ->and($release->dependencies)->toHaveCount(2);
});

it('skips php, extension and composer-plugin-api dependencies', function () {
    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    $action = new ProcessPackageDependency;
    $action->execute($release, [
        'php' => '^8.1',
        'ext-json' => '*',
        'lib-pcre' => '*',
        'composer-plugin-api' => '^2.0',
    ]);

    expect(Dependency::count())->toBe(0)
        ->and($release->dependencies)->toHaveCount(0);
});

it('dispatches AddDependencyDefaultByPackage for private dependencies', function () {
    Bus::fake([AddDependencyDefaultByPackage::class]);

    Http::fake([
        'repo.packagist.org/p2/vendor/private-dep.json' => Http::response([], 404),
    ]);

    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    $action = new ProcessPackageDependency;
    $action->execute($release, [
        'vendor/private-dep' => '^1.0',
    ], $package);

    Bus::assertDispatched(AddDependencyDefaultByPackage::class);
});

it('does not dispatch AddDependencyDefaultByPackage for public dependencies', function () {
    Bus::fake([AddDependencyDefaultByPackage::class]);

    Http::fake([
        'repo.packagist.org/p2/illuminate/support.json' => Http::response(['packages' => []], 200),
    ]);

    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    $action = new ProcessPackageDependency;
    $action->execute($release, [
        'illuminate/support' => '^10.0',
    ], $package);

    Bus::assertNotDispatched(AddDependencyDefaultByPackage::class);
});

it('does not dispatch AddDependencyDefaultByPackage when package is null', function () {
    Bus::fake([AddDependencyDefaultByPackage::class]);

    Http::fake([
        'repo.packagist.org/p2/vendor/private-dep.json' => Http::response([], 404),
    ]);

    $package = Package::factory()->create();
    $release = PackageRelease::factory()->create(['package_id' => $package->id]);

    $action = new ProcessPackageDependency;
    $action->execute($release, [
        'vendor/private-dep' => '^1.0',
    ]);

    Bus::assertNotDispatched(AddDependencyDefaultByPackage::class);
});

it('updates version array for existing dependency', function () {
    Http::fake([
        'repo.packagist.org/*' => Http::response(['packages' => []], 200),
    ]);

    $package = Package::factory()->create();
    $release1 = PackageRelease::factory()->create(['package_id' => $package->id, 'version' => '1.0.0']);
    $release2 = PackageRelease::factory()->create(['package_id' => $package->id, 'version' => '2.0.0']);

    $action = new ProcessPackageDependency;
    $action->execute($release1, ['illuminate/support' => '^10.0']);
    $action->execute($release2, ['illuminate/support' => '^11.0']);

    $dependency = Dependency::where('name', 'illuminate/support')->first();

    expect($dependency->versions)->toContain('^10.0')
        ->and($dependency->versions)->toContain('^11.0');
});
