<?php

namespace JeffersonGoncalves\LaravelSatis\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelSatis\Models\Token;

class TokenFactory extends Factory
{
    protected $model = Token::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => 'token',
            'token' => Token::generateCode('token'),
        ];
    }
}
