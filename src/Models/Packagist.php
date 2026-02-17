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
        return (config('satis.table_prefix') ?? '').'packagists';
    }
}
