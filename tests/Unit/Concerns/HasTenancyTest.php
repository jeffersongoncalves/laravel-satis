<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelSatis\Models\Package;

it('does not scope queries when tenancy is disabled', function () {
    config(['satis.tenancy.enabled' => false]);

    Package::factory()->create();
    Package::factory()->create();

    expect(Package::count())->toBe(2);
});

it('auto-assigns tenant foreign key on creation when tenancy is enabled', function () {
    config([
        'satis.tenancy.enabled' => true,
        'satis.tenancy.foreign_key' => 'team_id',
        'satis.tenancy.resolver' => fn () => 42,
    ]);

    Package::clearBootedModels();

    $package = Package::factory()->create();

    $row = DB::table('satis_packages')->where('id', $package->id)->first();

    expect((int) $row->team_id)->toBe(42);
});

it('scopes queries by tenant when tenancy is enabled', function () {
    config([
        'satis.tenancy.enabled' => true,
        'satis.tenancy.foreign_key' => 'team_id',
        'satis.tenancy.resolver' => fn () => 1,
    ]);

    Package::clearBootedModels();

    Package::factory()->create();

    // Insert a record for a different team directly to avoid scope
    DB::table('satis_packages')->insert([
        'name' => 'other/package',
        'type' => 'composer',
        'team_id' => 2,
        'is_credentials_validated' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // With resolver returning team_id=1, should only see 1 package
    expect(Package::count())->toBe(1);

    // Total in DB should be 2
    expect(DB::table('satis_packages')->count())->toBe(2);
});
