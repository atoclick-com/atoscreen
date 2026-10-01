<?php

namespace Tests\Feature\Api;

use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_fetch_public_playlist(): void
    {
        $screen = Screen::first();
        $response = $this->getJson("/api/v1/display/{$screen->id}/playlist");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'screen' => ['id', 'name', 'default_slide_duration', 'transition_effect', 'orientation'],
                'settings' => ['accent_color', 'ticker_enabled', 'clock_widget_enabled'],
                'slides',
                'checksum',
            ]);
    }

    public function test_can_send_display_heartbeat(): void
    {
        $screen = Screen::first();
        $slide = $screen->slides()->first();

        $response = $this->postJson("/api/v1/display/{$screen->id}/ping", [
            'current_slide_id' => $slide?->id,
            'duration_seconds' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ]);

        $this->assertNotNull($screen->fresh()->last_ping_at);
    }

    public function test_user_can_login_and_access_screens(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@atofood.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');

        $screensResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/screens');

        $screensResponse->assertStatus(200)
            ->assertJsonStructure(['screens']);
    }
}
