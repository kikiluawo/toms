<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_screen_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('OIL Management System')
            ->assertSee('Weekly Revenue')
            ->assertSee('Turbion');
    }

    public function test_login_screen_uses_the_horizon_layout(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('TURBION')
            ->assertSee('Access your oil operations dashboard')
            ->assertSee('Keep me logged In')
            ->assertDontSee('Sign In with Google')
            ->assertDontSee('Create an account')
            ->assertDontSee('Forgot Password?');
    }
}
