<?php

use JeffersonGoncalves\LaravelSatis\Enums\PackageType;

it('has composer case', function () {
    expect(PackageType::Composer->value)->toBe('composer');
});

it('has github case', function () {
    expect(PackageType::Github->value)->toBe('github');
});

it('can be created from string value', function () {
    expect(PackageType::from('composer'))->toBe(PackageType::Composer)
        ->and(PackageType::from('github'))->toBe(PackageType::Github);
});

it('returns a label for each case', function () {
    expect(PackageType::Composer->getLabel())->toBeString()
        ->and(PackageType::Github->getLabel())->toBeString();
});

it('creates instance from string using of()', function () {
    expect(PackageType::of('composer'))->toBe(PackageType::Composer)
        ->and(PackageType::of('github'))->toBe(PackageType::Github);
});

it('creates instance from self using of()', function () {
    expect(PackageType::of(PackageType::Composer))->toBe(PackageType::Composer)
        ->and(PackageType::of(PackageType::Github))->toBe(PackageType::Github);
});

it('throws TypeError from of() with invalid value', function () {
    PackageType::of('invalid');
})->throws(TypeError::class);

it('has exactly two cases', function () {
    expect(PackageType::cases())->toHaveCount(2);
});
