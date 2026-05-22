<?php

namespace App\Support\Inventory;

use Illuminate\Support\Str;

class InventorySerialNormalizer
{
    public static function normalize(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = Str::of($value)
            ->trim()
            ->ascii()
            ->upper()
            ->replaceMatches('/[\s\-]+/', '')
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->value();

        return $normalized !== '' ? $normalized : null;
    }

    public static function rowHash(array $payload): string
    {
        return hash('sha256', json_encode(self::sortRecursive($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function sortRecursive(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::sortRecursive($value);
            }
        }

        return $payload;
    }
}
