<?php

namespace Database\Seeders;

use App\Models\AiTool;
use Illuminate\Database\Seeder;

class AiToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tools = [
            [
                'fid'         => null,
                'key'         => 'search_goods',
                'name'        => 'Поиск товаров',
                'description' => 'Поиск товаров проекта по названию, артикулу или описанию. Возвращает список товаров с ценами.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'query' => [
                            'type'        => 'string',
                            'description' => 'Поисковый запрос (название, артикул или описание товара)',
                        ],
                        'limit' => [
                            'type'        => 'integer',
                            'description' => 'Максимальное количество результатов (по умолчанию 5)',
                            'default'     => 5,
                        ],
                    ],
                    'required'   => ['query'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'get_goods_by_id',
                'name'        => 'Детали товара по ID',
                'description' => 'Получить детальную информацию о товаре по его ID.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'goods_id' => [
                            'type'        => 'integer',
                            'description' => 'ID товара',
                        ],
                    ],
                    'required'   => ['goods_id'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'search_news',
                'name'        => 'Поиск новостей',
                'description' => 'Поиск новостей проекта. Возвращает заголовки, даты и превью новостей.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'query' => [
                            'type'        => 'string',
                            'description' => 'Поисковый запрос для поиска по заголовкам и содержанию новостей',
                        ],
                        'limit' => [
                            'type'        => 'integer',
                            'description' => 'Максимальное количество результатов (по умолчанию 5)',
                            'default'     => 5,
                        ],
                    ],
                    'required'   => ['query'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'get_project_info',
                'name'        => 'Информация о проекте',
                'description' => 'Получить информацию о текущем проекте (название, описание, контакты).',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => new \stdClass,
                    'required'   => [],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'search_docs',
                'name'        => 'Поиск документов',
                'description' => 'Поиск документов/статей проекта. Возвращает заголовки и содержимое документов.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'query' => [
                            'type'        => 'string',
                            'description' => 'Поисковый запрос для поиска по документам',
                        ],
                        'limit' => [
                            'type'        => 'integer',
                            'description' => 'Максимальное количество результатов (по умолчанию 5)',
                            'default'     => 5,
                        ],
                    ],
                    'required'   => ['query'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'search_knowledge_base',
                'name'        => 'Поиск по базе знаний',
                'description' => 'Поиск по базе знаний AI проекта. Используй когда пользователь задаёт вопрос, на который уже мог быть дан ответ ранее.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'query' => [
                            'type'        => 'string',
                            'description' => 'Поисковый запрос',
                        ],
                        'limit' => [
                            'type'        => 'integer',
                            'description' => 'Максимальное количество результатов (по умолчанию 5)',
                            'default'     => 5,
                        ],
                    ],
                    'required'   => ['query'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'get_goods_categories',
                'name'        => 'Категории товаров',
                'description' => 'Получить список категорий/разделов товаров проекта.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => new \stdClass,
                    'required'   => [],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'fetch_and_save_page',
                'name'        => 'Парсинг веб-страницы',
                'description' => 'Загрузить веб-страницу по URL, извлечь текст и сохранить его в базу знаний проекта. Используй когда пользователь просит сохранить информацию с сайта или проанализировать страницу.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'url' => [
                            'type'        => 'string',
                            'description' => 'Полный URL страницы для парсинга (https://...)',
                        ],
                        'category' => [
                            'type'        => 'string',
                            'description' => 'Категория знания (по умолчанию web_page). Можно указать: faq, manual, docs, token, fund, invest, news',
                            'default'     => 'web_page',
                        ],
                    ],
                    'required'   => ['url'],
                ],
                'active'      => true,
            ],
            [
                'fid'         => null,
                'key'         => 'save_to_knowledge_base',
                'name'        => 'Сохранение в базу знаний',
                'description' => 'Сохранить информацию в базу знаний проекта. Используй когда пользователь предоставляет полезную информацию, которую следует запомнить для других пользователей.',
                'schema'      => [
                    'type'       => 'object',
                    'properties' => [
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Заголовок или тема информации',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Содержание информации для сохранения',
                        ],
                        'category' => [
                            'type'        => 'string',
                            'description' => 'Категория знания (по умолчанию manual). Можно указать: faq, manual, docs, token, fund, invest, news, chat_export',
                            'default'     => 'manual',
                        ],
                    ],
                    'required'   => ['title', 'content'],
                ],
                'active'      => true,
            ],
        ];

        foreach ($tools as $tool) {
            AiTool::create($tool);
        }

        $this->command->info('Добавлено ' . count($tools) . ' инструментов в ai_tools.');
    }
}
