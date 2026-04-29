<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    public function test_api_auth_config_returns_google_client_id(): void
    {
        $response = $this->getJson('/api/auth/config');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'googleClientId',
        ]);
    }

    public function test_api_login_returns_422_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_api_login_returns_422_when_password_missing(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => 'user@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_goods_search_returns_empty_array_when_query_missing(): void
    {
        $response = $this->getJson('/api/goods/search');

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_goods_search_returns_empty_array_when_query_too_short(): void
    {
        $response = $this->getJson('/api/goods/search?q=a');

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }
}
