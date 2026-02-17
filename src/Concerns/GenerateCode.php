<?php

namespace JeffersonGoncalves\LaravelSatis\Concerns;

use Illuminate\Support\Str;

trait GenerateCode
{
    abstract public static function getColumnCode(): array;

    abstract public static function getLengthCode(): array;

    public static function generateCode(string $column): string
    {
        do {
            $code = Str::random(self::getLengthCodeByColumn($column));
        } while (self::query()->where($column, $code)->count());

        return $code;
    }

    public static function getLengthCodeByColumn(string $column): int
    {
        $lengths = self::getLengthCode();

        return $lengths[$column] ?? 8;
    }
}
