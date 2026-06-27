<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_client_theme_fields(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.clients.update', $client), [
            'name' => 'Acme', 'slug' => 'acme',
            'brand_color' => '#112233', 'accent_color' => '#445566',
            'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id, 'brand_color' => '#112233',
            'accent_color' => '#445566', 'theme_mode' => 'dark', 'bg_style' => 'aurora',
        ]);
    }

    public function test_invalid_theme_mode_is_rejected(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.clients.update', $client), [
            'name' => 'Acme', 'slug' => 'acme', 'theme_mode' => 'rainbow',
        ])->assertSessionHasErrors('theme_mode');
    }
}
