<?php

use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;

it('has public case', function () {
    expect(DependencyType::Public->value)->toBe('public');
});

it('has private case', function () {
    expect(DependencyType::Private->value)->toBe('private');
});

it('can be created from string value', function () {
    expect(DependencyType::from('public'))->toBe(DependencyType::Public)
        ->and(DependencyType::from('private'))->toBe(DependencyType::Private);
});

it('returns a label for each case', function () {
    expect(DependencyType::Public->getLabel())->toBeString()
        ->and(DependencyType::Private->getLabel())->toBeString();
});

it('has exactly two cases', function () {
    expect(DependencyType::cases())->toHaveCount(2);
});
