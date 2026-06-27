<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_redesign(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Welcome back');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertStatus(200)->assertSee('Create your account');
    }
}
