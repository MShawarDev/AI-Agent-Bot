<?php

namespace Tests\Feature;

use App\Models\Client;
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
}
