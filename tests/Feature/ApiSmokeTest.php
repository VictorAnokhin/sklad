<?php

namespace Tests\Feature;

use App\Http\Controllers\GoodsController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    public function test_api_auth_config_returns_google_client_id(): void
    {
        $response = $this->getJson('/api/auth/config');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'googleClientId',
            'phoneAuthEnabled',
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

    public function test_api_phone_send_code_requires_phone(): void
    {
        $response = $this->postJson('/api/auth/phone/send-code', []);

        $response->assertStatus(422);
    }

    public function test_api_phone_verify_requires_code(): void
    {
        $response = $this->postJson('/api/auth/phone/verify', [
            'phone' => '+380671234567',
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

    public function test_goods_catalog_filter_groups_returns_json_structure(): void
    {
        $response = $this->getJson('/api/goods/catalog-filter-groups');

        $response->assertStatus(200);
        $response->assertJsonStructure(['groups']);
    }

    public function test_manager_ai_goods_by_category_rejects_missing_bridge_auth(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this->getJson(
            '/api/goods/manager-ai/items/by-category?fid=2&idglava=2219&idcaption=2171'
        );

        $response->assertStatus(403);
    }

    public function test_manager_ai_goods_by_category_requires_category_filter(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this
            ->withHeader('X-ManagerAI-Bridge-Secret', 'test-secret')
            ->getJson('/api/goods/manager-ai/items/by-category?fid=2');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['idglava', 'idcaption']);
    }

    public function test_manager_ai_goods_by_pnum_rejects_missing_bridge_auth(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this->getJson(
            '/api/goods/manager-ai/items/by-pnum?fid=2&pnum=21042'
        );

        $response->assertStatus(403);
    }

    public function test_manager_ai_goods_by_pnum_requires_pnum(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this
            ->withHeader('X-ManagerAI-Bridge-Secret', 'test-secret')
            ->getJson('/api/goods/manager-ai/items/by-pnum?fid=2');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pnum']);
    }

    public function test_manager_ai_goods_search_rejects_missing_bridge_auth(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this->getJson('/api/goods/manager-ai/search?fid=2&q=рама');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid ManagerAI bridge secret.',
        ]);
    }

    public function test_manager_ai_goods_search_accepts_signed_query_token(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $fid = 2;
        $expires = now()->addMinutes(5)->timestamp;
        $token = hash_hmac('sha256', implode('|', ['manager-ai-goods', $fid, $expires]), 'test-secret');

        $response = $this->getJson(
            '/api/goods/manager-ai/search?fid='.$fid
            .'&manager_ai_expires='.$expires
            .'&manager_ai_token='.$token
            .'&q=a'
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Parameter "q" must contain at least 2 characters.',
        ]);
    }

    public function test_manager_ai_item_update_validator_allows_description_only_with_id(): void
    {
        $payload = $this->validateManagerAiItemPayload([
            'fid' => 2,
            'description' => '<p>HTML description</p>',
        ], false, false);

        $this->assertSame(2, $payload['fid']);
        $this->assertSame('<p>HTML description</p>', $payload['description']);
    }

    public function test_manager_ai_item_upsert_validator_still_requires_source_identity(): void
    {
        $this->expectException(ValidationException::class);

        $this->validateManagerAiItemPayload([
            'fid' => 2,
            'description' => '<p>HTML description</p>',
        ], false, true);
    }

    public function test_manager_ai_projects_rejects_missing_bridge_auth(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this->getJson('/api/projects/manager-ai?email=av8.fund@gmail.com');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Invalid ManagerAI bridge secret.',
        ]);
    }

    public function test_manager_ai_projects_accepts_bridge_auth(): void
    {
        config(['services.manager_ai.bridge_secret' => 'test-secret']);

        $response = $this
            ->withHeader('X-ManagerAI-Bridge-Secret', 'test-secret')
            ->getJson('/api/projects/manager-ai?email=av8.fund@gmail.com');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'email',
            'items',
        ]);
        $response->assertJson([
            'success' => true,
            'email' => 'av8.fund@gmail.com',
        ]);
    }

    private function validateManagerAiItemPayload(array $payload, bool $creating, bool $requiresSourceIdentity): array
    {
        $request = Request::create('/api/goods/manager-ai/items/123', 'PUT', $payload);
        $method = new ReflectionMethod(GoodsController::class, 'validateManagerAiItemPayload');
        $method->setAccessible(true);

        return $method->invoke(new GoodsController(), $request, $creating, $requiresSourceIdentity);
    }
}
