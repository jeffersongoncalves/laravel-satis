<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LaravelSatis\Models\Credential;

class ValidateCredential
{
    /**
     * @return array{success: bool, message: string}
     */
    public function execute(Credential $credential): array
    {
        try {
            $response = Http::withBasicAuth($credential->email, $credential->password)
                ->get("{$credential->url}/packages.json");

            if ($response->successful()) {
                $credential->update([
                    'is_validated' => true,
                    'validated_at' => now(),
                ]);

                return ['success' => true, 'message' => 'Credential validated successfully.'];
            }

            $credential->update([
                'is_validated' => false,
                'validated_at' => null,
            ]);

            return [
                'success' => false,
                'message' => "Validation failed with status {$response->status()}.",
            ];
        } catch (\Exception $e) {
            $credential->update([
                'is_validated' => false,
                'validated_at' => null,
            ]);

            return [
                'success' => false,
                'message' => "Connection error: {$e->getMessage()}",
            ];
        }
    }
}
