<?php

namespace App\Support;

use App\Models\Client;

class Theme
{
    public static function forClient(?Client $client): array
    {
        $d = config('theme.defaults');
        $brand = self::clean($client?->brand_color) ?: $d['brand'];
        $accent = self::clean($client?->accent_color) ?: $brand;
        $mode = in_array($client?->theme_mode, ['light', 'dark', 'auto'], true) ? $client->theme_mode : $d['mode'];
        $bg = in_array($client?->bg_style, ['mesh', 'aurora', 'solid', 'dots'], true) ? $client->bg_style : $d['bg'];

        return [
            'brand' => $brand,
            'accent' => $accent,
            'mode' => $mode,
            'bg' => $bg,
            'brand_rgb' => self::hexToRgb($brand),
            'accent_rgb' => self::hexToRgb($accent),
        ];
    }

    public static function current(): array
    {
        return self::forClient(auth()->user()?->client);
    }

    private static function clean(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? strtolower($hex) : null;
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "$r $g $b";
    }
}
