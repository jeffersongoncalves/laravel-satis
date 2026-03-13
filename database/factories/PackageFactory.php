<?php

namespace JeffersonGoncalves\LaravelSatis\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\LaravelSatis\Enums\PackageType;
use JeffersonGoncalves\LaravelSatis\Models\Credential;
use JeffersonGoncalves\LaravelSatis\Models\Package;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().'/'.$this->faker->word(),
            'type' => PackageType::Composer,
            'credential_id' => Credential::factory(),
            'is_dev' => false,
            'is_credentials_validated' => false,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn () => [
            'is_credentials_validated' => true,
            'credentials_validated_at' => now(),
        ]);
    }

    public function dev(): static
    {
        return $this->state(fn () => [
            'is_dev' => true,
        ]);
    }

    public function github(): static
    {
        return $this->state(fn () => [
            'type' => PackageType::Github,
            'credential_id' => Credential::factory()->github(),
        ]);
    }

    public function withoutCredential(): static
    {
        return $this->state(fn () => [
            'credential_id' => null,
        ]);
    }
}
