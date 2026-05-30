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
}
