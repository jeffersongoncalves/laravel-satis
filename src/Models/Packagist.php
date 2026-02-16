<?php

namespace JeffersonGoncalves\LaravelSatis\Models;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\LaravelSatis\Enums\DependencyType;

class Packagist extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'type' => DependencyType::class,
    ];

    public function getTable(): string
    {
        return config('laravel-satis.table_prefix', 'satis_').'packagists';
    }
}
