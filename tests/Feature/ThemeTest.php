<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_persists_theme_fields(): void
    {
        $client = Client::create([
            'name' => 'Acme', 'slug' => 'acme',
            'accent_color' => '#06b6d4', 'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id, 'accent_color' => '#06b6d4',
            'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);
    }

    public function test_theme_falls_back_to_defaults(): void
    {
        $theme = Theme::forClient(null);
        $this->assertSame('#4f46e5', $theme['brand']);
        $this->assertSame('79 70 229', $theme['brand_rgb']);
        $this->assertSame('mesh', $theme['bg']);
    }

    public function test_theme_uses_client_values_and_accent_defaults_to_brand(): void
    {
        $client = Client::create([
            'name' => 'Acme', 'slug' => 'acme',
            'brand_color' => '#ff0000', 'theme_mode' => 'dark',
        ]);
        $theme = Theme::forClient($client);
        $this->assertSame('#ff0000', $theme['brand']);
        $this->assertSame('255 0 0', $theme['brand_rgb']);
        $this->assertSame('#ff0000', $theme['accent']); // accent blank -> brand
        $this->assertSame('dark', $theme['mode']);
    }
}
