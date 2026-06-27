<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_redesign(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Welcome back');
    }

    public function test_dashboard_renders_overview(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200)->assertSee('your workspace at a glance', false);
    }

    public function test_chat_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme', 'bot_name' => 'Acme Bot']);
        $user = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('chat'))->assertStatus(200)->assertSee('Acme Bot');
    }

    public function test_reports_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('reports.index'))->assertStatus(200)->assertSee('Drop a report here', false);
    }

    public function test_profile_page_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->get(route('profile.edit'))->assertStatus(200);
    }

    public function test_admin_clients_index_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.clients.index'))->assertStatus(200)->assertSee('Acme');
    }

    public function test_admin_users_create_renders(): void
    {
        $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = User::factory()->create(['client_id' => $client->id, 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.clients.users.create', $client))->assertStatus(200);
    }
}
