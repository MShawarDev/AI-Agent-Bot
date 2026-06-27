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
}
