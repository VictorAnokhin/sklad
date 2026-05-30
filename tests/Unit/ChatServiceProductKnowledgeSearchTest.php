<?php

namespace Tests\Unit;

use App\Services\ChatService;
use ReflectionClass;
use Tests\TestCase;

class ChatServiceProductKnowledgeSearchTest extends TestCase
{
    public function test_product_knowledge_queries_include_searchable_stems(): void
    {
        $reflection = new ReflectionClass(ChatService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('productKnowledgeSearchQueries');
        $method->setAccessible(true);

        $queries = $method->invoke($service, 'Как заказать рамку с рекламой?', [
            'topic' => 'заказать рамку с рекламой?',
        ]);

        $this->assertContains('рамк', $queries);
        $this->assertContains('реклам', $queries);
    }

    public function test_product_availability_question_is_catalog_search_request(): void
    {
        $reflection = new ReflectionClass(ChatService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $isSearchRequest = $reflection->getMethod('isProductCatalogSearchRequest');
        $isSearchRequest->setAccessible(true);

        $searchQuery = $reflection->getMethod('productCatalogSearchQuery');
        $searchQuery->setAccessible(true);

        $intent = [
            'type' => 'faq',
            'topic' => 'наличие квадратных рамок с надписью',
            'reason' => 'ai_detected',
            'needs_tools' => false,
        ];

        $this->assertTrue($isSearchRequest->invoke($service, 'есть квадратные рамки с надписью?', $intent));

        $query = $searchQuery->invoke($service, 'есть квадратные рамки с надписью?', $intent);

        $this->assertStringContainsString('квадратн', $query);
        $this->assertStringContainsString('рамк', $query);
        $this->assertStringContainsString('надпис', $query);
    }
}
