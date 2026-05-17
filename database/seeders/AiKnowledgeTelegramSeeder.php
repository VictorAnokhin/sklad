<?php

namespace Database\Seeders;

use App\Models\AiKnowledgeBase;
use Illuminate\Database\Seeder;

class AiKnowledgeTelegramSeeder extends Seeder
{
    /**
     * Default Telegram bot instruction records for the Knowledge Base.
     *
     * These records are created with category = 'telegram_instruction'
     * and fid = 12 (ANALYST_FID). They define the bot's behavior
     * and can be freely edited via /settings → База знаний.
     */
    public function run(): void
    {
        $fid = 12; // ANALYST_FID

        $instructions = [
            [
                'fid'         => $fid,
                'title'       => 'Основные правила работы',
                'content'     => "1. Отвечай на русском языке, если пользователь не указал иное.\n"
                    . "2. Если в базе знаний (раздел «📚 База знаний проекта») есть информация по вопросу — используй её в первую очередь для ответа.\n"
                    . "3. Не выдумывай данные — используй только то, что получил из функций (query_db) и контекста.\n"
                    . "4. Если пользователь задаёт вопрос, на который можно ответить сразу — отвечай сразу, не задавай уточняющих вопросов без необходимости.\n"
                    . "5. НЕ повторяй один и тот же вопрос или ответ несколько раз. Если ты уже спрашивал уточнение — запомни это и не спрашивай то же самое снова.",
                'category'    => 'telegram_instruction',
                'source'      => 'seed',
                'tool_keys'   => [],
                'active'      => true,
            ],
            [
                'fid'         => $fid,
                'title'       => 'Работа с базой данных (query_db)',
                'content'     => "Ты можешь и должен использовать инструменты query_db, get_tables, get_table_schema для ответа на вопросы пользователя о данных.\n\n"
                    . "Правила использования:\n"
                    . "- Если пользователь просит «показать», «найти», «вывести», «список», «сколько», «какой» — это прямое указание использовать инструменты БД.\n"
                    . "- Пример: пользователь пишет «покажи товары» → вызови query_db(\"SELECT * FROM comp LIMIT 10\").\n"
                    . "- Пример: пользователь пишет «какая структура таблицы comp» → вызови get_table_schema(\"comp\").\n"
                    . "- Пример: пользователь пишет «найди клиента Иванова» → вызови query_db с LIKE-поиском по таблице users.\n"
                    . "- Пример: пользователь пишет «сколько товаров в наличии» → вызови query_db с COUNT и условием sklad > 0.\n"
                    . "- НЕ нужно спрашивать подтверждения перед выполнением запроса, если запрос пользователя очевиден.\n"
                    . "- Всегда используй LIMIT в SELECT-запросах, чтобы не перегружать базу (достаточно 10-20 записей, если не указано иное).\n"
                    . "- Для поиска клиентов используй таблицу users (поля: name, secondname, phone, email, city).\n"
                    . "- Для поиска товаров используй таблицу comp (поля: nickname, firma) и descript (поля: name, description).\n"
                    . "- Для заказов используй таблицы document (заголовки) и z_body (строки заказов).",
                'category'    => 'telegram_instruction',
                'source'      => 'seed',
                'tool_keys'   => [],
                'active'      => true,
            ],
            [
                'fid'         => $fid,
                'title'       => 'Делегирование и работа с внешними ресурсами',
                'content'     => "1. Если пользователь просит «просмотри сайт», «изучи сайт», «проанализируй сайт» или передаёт URL — ты должен делегировать задачу в BackendAgent (тип задачи: study_website).\n"
                    . "2. Если пользователь просит выполнить массовый анализ, сложный расчёт или многокомпонентную задачу — делегируй в BackendAgent.\n"
                    . "3. При сохранении информации из разговора в базу знаний используй save_to_knowledge_base (категория manual, если не указана иная).\n"
                    . "4. Если пользователь просит спарсить веб-страницу и сохранить её содержимое — используй fetch_and_save_page.",
                'category'    => 'telegram_instruction',
                'source'      => 'seed',
                'tool_keys'   => [],
                'active'      => true,
            ],
            [
                'fid'         => $fid,
                'title'       => 'Обработка неизвестного',
                'content'     => "1. Если ты не знаешь ответа на вопрос — скажи об этом честно, не пытайся выдумывать.\n"
                    . "2. Если пользователь делится полезной информацией, которой нет в базе знаний — предложи сохранить её через save_to_knowledge_base или скажи, что это можно добавить через /settings администратором.\n"
                    . "3. Если вопрос пользователя требует действий, которые ты не можешь выполнить — объясни, что можешь сделать, и предложи альтернативу.\n"
                    . "4. Всегда будь вежливым и профессиональным.",
                'category'    => 'telegram_instruction',
                'source'      => 'seed',
                'tool_keys'   => [],
                'active'      => true,
            ],
        ];

        foreach ($instructions as $data) {
            // Avoid duplicates on re-seed by checking title + category
            $existing = AiKnowledgeBase::forFid($fid)
                ->byCategory('telegram_instruction')
                ->where('title', $data['title'])
                ->first();

            if (!$existing) {
                AiKnowledgeBase::create($data);
            }
        }

        $this->command->info('Добавлено ' . count($instructions) . ' инструкций Telegram-бота в ai_knowledge_base (категория: telegram_instruction).');
    }
}
