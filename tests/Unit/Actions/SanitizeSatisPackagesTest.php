<?php

use JeffersonGoncalves\LaravelSatis\Actions\SanitizeSatisPackages;

it('removes ANSI color codes', function () {
    $action = new SanitizeSatisPackages;
    $output = "\e[32mSuccess\e[0m: Package built";

    $result = $action->execute($output);

    expect($result)->toBe('Success: Package built');
});

it('removes empty lines', function () {
    $action = new SanitizeSatisPackages;
    $output = "Line 1\n\n\nLine 2\n\nLine 3";

    $result = $action->execute($output);

    expect($result)->toBe("Line 1\nLine 2\nLine 3");
});

it('trims whitespace from lines', function () {
    $action = new SanitizeSatisPackages;
    $output = "  Line 1  \n  Line 2  ";

    $result = $action->execute($output);

    expect($result)->toBe("Line 1\nLine 2");
});

it('handles empty output', function () {
    $action = new SanitizeSatisPackages;

    $result = $action->execute('');

    expect($result)->toBe('');
});

it('handles multiple ANSI codes in one line', function () {
    $action = new SanitizeSatisPackages;
    $output = "\e[1;33mWarning\e[0m: \e[31mError\e[0m occurred";

    $result = $action->execute($output);

    expect($result)->toBe('Warning: Error occurred');
});
