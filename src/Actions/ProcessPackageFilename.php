<?php

namespace JeffersonGoncalves\LaravelSatis\Actions;

class ProcessPackageFilename
{
    public function execute(string $filename): array
    {
        $parts = explode('/', $filename);

        if (count($parts) >= 2) {
            return [
                'vendor' => $parts[0],
                'package' => $parts[1],
                'full_name' => $parts[0].'/'.$parts[1],
            ];
        }

        $parts = explode('-', $filename);

        if (count($parts) >= 2) {
            return [
                'vendor' => $parts[0],
                'package' => $parts[1],
                'full_name' => $parts[0].'/'.$parts[1],
            ];
        }

        return [
            'vendor' => null,
            'package' => $filename,
            'full_name' => $filename,
        ];
    }
}
