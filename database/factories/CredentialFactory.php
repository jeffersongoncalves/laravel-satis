<?php

namespace JeffersonGoncalves\LaravelSatis\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelSatis\Models\Credential;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        $host = $this->faker->domainName();

        return [
            'name' => $host,
            'url' => 'https://'.$host,
            'email' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'is_validated' => false,
            'validated_at' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'is_validated' => true,
            'validated_at' => now(),
        ]);
    }

    public function github(): static
    {
        return $this->state(fn () => [
            'name' => 'GitHub',
            'url' => 'https://github.com/'.$this->faker->word().'/'.$this->faker->word().'.git',
        ]);
    }
}
