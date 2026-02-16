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

it('has exactly two cases', function () {
    expect(PackageType::cases())->toHaveCount(2);
});
