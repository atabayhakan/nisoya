<?php

namespace App\Support\GlobalCommand;

use Illuminate\Support\Str;

final class CityName
{
    public static function normalize(?string $name): string
    {
        $name = Str::ascii(mb_strtolower(trim($name ?? ''), 'UTF-8'), 'tr');

        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? '');
    }

    public static function key(?string $name): ?string
    {
        $name = self::normalize($name);

        return $name === '' ? null : hash('sha256', $name);
    }
}
