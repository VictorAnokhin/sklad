<?php

namespace App\Agents;

use App\Models\AgentTask;
use App\Models\Document;
use App\Models\User;
use App\Models\ZBody;
use App\Services\AiKnowledgeService;
use App\Services\ChatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackendAgent
{
    public function __construct(
        private AiKnowledgeService $knowledgeService,
        private ChatService $chatService,
    ) {}

    /**
     * Выполнить задачу, делегированную от другого агента.
     */
    public function executeTask(AgentTask $task): array
    {
        return match ($task->task_type) {
            // ── Клиенты ──
            'find_client'        => $this->findClient($task),
            'get_client'         => $this->getClient($task),
            'create_client'      => $this->createClient($task),
            'update_client'      => $this->updateClient($task),
            'get_client_balance' => $this->getClientBalance($task),

            // ── Заказы ──
            'find_order'         => $this->findOrder($task),
            'find_client_orders' => $this->findClientOrders($task),
            'create_order'       => $this->createOrder($task),
            'get_order_status'   => $this->getOrderStatus($task),

            // ── Знания / Анализ ──
            'study_website'      => $this->studyWebsite($task),
            'save_to_knowledge'  => $this->saveToKnowledge($task),
            'mass_analysis'      => $this->massAnalysis($task),
            'complex_question'   => $this->complexQuestion($task),

            default => throw new \InvalidArgumentException("BackendAgent: unknown task_type '{$task->task_type}'"),
        };
    }

    // ════════════════════════════════════════════════════════════════
    //  КЛИЕНТЫ
    // ════════════════════════════════════════════════════════════════

    /**
     * Найти клиента по телефону, имени, email.
     */
    private function findClient(AgentTask $task): array
    {
        $fid = $task->fid;
        $query = $task->input_data['query'] ?? '';

        $result = User::userslist($fid, ['search' => $query], 0, 10);

        $clients = collect($result['clients'] ?? [])->map(fn ($c) => [
            'id' => (int) $c->id,
            'name' => trim(($c->secondname ?? '') . ' ' . ($c->name ?? '') . ' ' . ($c->fathername ?? '')),
            'orgname' => $c->orgname ?? '',
            'phone' => $c->phone ?? '',
            'email' => $c->email ?? '',
            'city' => $c->city ?? '',
        ]);

        if ($result['total'] === 0) {
            return [
                'found' => false,
                'message' => "Клиенты по запросу «{$query}» не найдены.",
                'clients' => [],
            ];
        }

        return [
            'found' => true,
            'message' => "Найдено {$result['total']} клиентов: " . $clients->pluck('name')->implode(', '),
            'clients' => $clients->toArray(),
            'total' => $result['total'],
        ];
    }

    /**
     * Показать детали клиента.
     */
    private function getClient(AgentTask $task): array
    {
        $fid = $task->fid;
        $clientId = $task->input_data['client_id'];

        $result = User::showClient($clientId, $fid);

        if (!$result['client']) {
            return [
                'found' => false,
                'message' => "Клиент ID {$clientId} не найден.",
            ];
        }

        $c = $result['client'];

        return [
            'found' => true,
            'client' => [
                'id' => (int) $c->id,
                'login' => $c->login ?? '',
                'name' => trim(($c->secondname ?? '') . ' ' . ($c->name ?? '') . ' ' . ($c->fathername ?? '')),
                'orgname' => $c->orgname ?? '',
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
                'city' => $c->city ?? '',
                'region' => $c->region ?? '',
                'address' => $c->poshta ?? '',
            ],
        ];
    }

    /**
     * Создать нового клиента.
     */
    private function createClient(AgentTask $task): array
    {
        $data = $task->input_data;
        $data['firma'] = $task->fid;

        try {
            $clientId = User::edit('0', $data);

            return [
                'client_id' => $clientId,
                'message' => "Клиент создан, ID: {$clientId}.",
            ];
        } catch (\Throwable $e) {
            Log::error('BackendAgent: createClient failed', ['error' => $e->getMessage()]);

            return [
                'client_id' => null,
                'message' => "Ошибка создания клиента: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Обновить данные клиента.
     */
    private function updateClient(AgentTask $task): array
    {
        $clientId = $task->input_data['client_id'];
        $data = $task->input_data['data'] ?? [];

        try {
            User::edit((string) $clientId, $data);

            return [
                'client_id' => $clientId,
                'message' => "Клиент ID {$clientId} обновлён.",
            ];
        } catch (\Throwable $e) {
            return [
                'message' => "Ошибка обновления клиента: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Показать баланс и долг клиента.
     */
    private function getClientBalance(AgentTask $task): array
    {
        $clientId = $task->input_data['client_id'];
        $fid = $task->fid;

        $saldo = Document::saldo($clientId, $fid);

        $debt = (float) $saldo['debt'];
        $paid = (float) $saldo['paid'];
        $balance = (float) $saldo['balance'];

        $status = $balance >= 0 ? '✅ Баланс положительный' : '⚠️ Есть задолженность';

        return [
            'client_id' => (int) $clientId,
            'debt' => $debt,
            'paid' => $paid,
            'balance' => $balance,
            'message' => "Баланс клиента ID {$clientId}: долг {$debt} грн, оплачено {$paid} грн. {$status}",
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  ЗАКАЗЫ
    // ════════════════════════════════════════════════════════════════

    /**
     * Найти заказ по номеру.
     */
    private function findOrder(AgentTask $task): array
    {
        $fid = $task->fid;
        $num = $task->input_data['num'] ?? $task->input_data['order_num'] ?? '';

        $order = DB::table('document')
            ->where('firma', $fid)
            ->where('num', $num)
            ->first();

        if (!$order) {
            return [
                'found' => false,
                'message' => "Заказ №{$num} не найден.",
            ];
        }

        $items = ZBody::where('docid', $order->id)->get();

        $client = DB::table('users')->where('id', $order->client1)->first();

        return [
            'found' => true,
            'message' => "Заказ №{$order->num} от {$order->data}, сумма: {$order->summa} грн, клиент: " . ($client->name ?? $order->client1),
            'order' => [
                'id' => (int) $order->id,
                'num' => $order->num,
                'date' => $order->data,
                'summa' => (float) $order->summa,
                'status' => $order->status,
                'content' => $order->content,
                'client_name' => $client ? trim(($client->secondname ?? '') . ' ' . ($client->name ?? '') . ' ' . ($client->fathername ?? '')) : '',
            ],
            'items' => $items->map(fn ($i) => [
                'pnum' => (int) $i->pnum,
                'count' => (float) $i->pcount,
                'price' => (float) $i->pprice,
                'summa' => (float) $i->psumma,
            ])->toArray(),
        ];
    }

    /**
     * Найти все заказы клиента.
     */
    private function findClientOrders(AgentTask $task): array
    {
        $fid = $task->fid;
        $clientId = $task->input_data['client_id'];

        $orders = DB::table('document')
            ->where('firma', $fid)
            ->where('client1', $clientId)
            ->where('type', 'ZOUT')
            ->orderByDesc('dt')
            ->limit(20)
            ->get();

        if ($orders->isEmpty()) {
            return [
                'found' => false,
                'message' => "Заказы для клиента ID {$clientId} не найдены.",
                'orders' => [],
            ];
        }

        return [
            'found' => true,
            'message' => "Найдено {$orders->count()} заказов: " . $orders->pluck('num')->implode(', '),
            'orders' => $orders->map(fn ($o) => [
                'id' => (int) $o->id,
                'num' => $o->num,
                'date' => $o->data,
                'summa' => (float) $o->summa,
                'status' => $o->status,
            ])->toArray(),
        ];
    }

    /**
     * Создать новый заказ.
     */
    private function createOrder(AgentTask $task): array
    {
        $fid = $task->fid;
        $input = $task->input_data;

        $clientId = $input['client_id'];
        $items = $input['items'] ?? [];
        $content = $input['content'] ?? 'Создано AI-агентом';

        if (empty($clientId)) {
            return ['message' => 'Ошибка: не указан client_id.'];
        }

        if (empty($items)) {
            return ['message' => 'Ошибка: не указаны товары в заказе.'];
        }

        try {
            $year = date('Y');
            $num = Document::nextNum('ZOUT', $fid, $year);

            $totalSumma = collect($items)->sum(fn ($item) => ($item['count'] ?? 1) * ($item['price'] ?? 0));

            $orderId = DB::table('document')->insertGetId([
                'num' => $num,
                'type' => 'ZOUT',
                'firma' => $fid,
                'client1' => $clientId,
                'data' => date('d-m-Y'),
                'time' => date('H:i:s'),
                'dt' => now(),
                'summa' => $totalSumma,
                'content' => $content,
                'manager' => 'ai_backend_agent',
            ]);

            foreach ($items as $item) {
                ZBody::create([
                    'docid' => $orderId,
                    'docnum' => $num,
                    'pnum' => $item['pnum'] ?? 0,
                    'pcount' => $item['count'] ?? 1,
                    'pprice' => $item['price'] ?? 0,
                    'psumma' => ($item['count'] ?? 1) * ($item['price'] ?? 0),
                    'type' => 'ZOUT',
                    'firma' => $fid,
                ]);
            }

            return [
                'order_id' => (int) $orderId,
                'num' => $num,
                'summa' => $totalSumma,
                'message' => "Заказ №{$num} создан, сумма: {$totalSumma} грн.",
            ];
        } catch (\Throwable $e) {
            Log::error('BackendAgent: createOrder failed', ['error' => $e->getMessage()]);

            return [
                'message' => "Ошибка создания заказа: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Получить статус заказа.
     */
    private function getOrderStatus(AgentTask $task): array
    {
        $fid = $task->fid;
        $orderId = $task->input_data['order_id'] ?? 0;

        $order = DB::table('document')
            ->where('id', $orderId)
            ->where('firma', $fid)
            ->first();

        if (!$order) {
            return ['message' => "Заказ ID {$orderId} не найден."];
        }

        $statusText = match ((int) $order->provodka) {
            1 => '✅ Проведён',
            0 => '⏳ Ожидает проводки',
            default => '❓ Неизвестно',
        };

        return [
            'order_id' => (int) $order->id,
            'num' => $order->num,
            'status' => $statusText,
            'provodka' => (int) $order->provodka,
            'close' => (int) ($order->close ?? 0) === 1 ? 'Закрыт' : 'Открыт',
            'message' => "Заказ №{$order->num}: {$statusText}",
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  ЗНАНИЯ / АНАЛИЗ
    // ════════════════════════════════════════════════════════════════

    /**
     * Изучить сайт и сохранить в knowledge base.
     */
    private function studyWebsite(AgentTask $task): array
    {
        $fid = $task->fid;
        $url = $task->input_data['url'] ?? '';

        if (empty($url)) {
            return ['message' => 'Ошибка: не указан URL сайта.'];
        }

        try {
            $result = $this->knowledgeService->fetchAndSavePage($fid, $url);

            return [
                'saved' => true,
                'message' => "Сайт {$url} проанализирован и сохранён в базу знаний.",
                'data' => $result,
            ];
        } catch (\Throwable $e) {
            return [
                'saved' => false,
                'message' => "Ошибка анализа сайта: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Сохранить информацию в knowledge base.
     */
    private function saveToKnowledge(AgentTask $task): array
    {
        $fid = $task->fid;
        $title = $task->input_data['title'] ?? '';
        $content = $task->input_data['content'] ?? '';
        $category = $task->input_data['category'] ?? 'ai_saved';

        try {
            $record = $this->knowledgeService->saveInformation($fid, $title, $content, $category);

            return [
                'saved' => true,
                'record_id' => $record->id,
                'message' => "Информация «{$title}» сохранена в базу знаний.",
            ];
        } catch (\Throwable $e) {
            return [
                'saved' => false,
                'message' => "Ошибка сохранения: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Массовый анализ данных через DeepSeek.
     */
    private function massAnalysis(AgentTask $task): array
    {
        $fid = $task->fid;
        $query = $task->input_data['query'] ?? '';
        $language = $task->input_data['language'] ?? 'ru';

        $result = $this->chatService->sendMessage([
            'fid' => $fid,
            'message' => $query,
            'language' => $language,
            'useDbTools' => true,
        ]);

        return [
            'answer' => $result['response'] ?? 'Анализ завершён.',
            'sources' => $result['sources'] ?? [],
            'knowledge_updated' => $result['knowledge_updated'] ?? false,
        ];
    }

    /**
     * Сложный вопрос через DeepSeek.
     */
    private function complexQuestion(AgentTask $task): array
    {
        $fid = $task->fid;
        $question = $task->input_data['question'] ?? $task->input_data['query'] ?? '';
        $language = $task->input_data['language'] ?? 'ru';

        $result = $this->chatService->sendMessage([
            'fid' => $fid,
            'message' => $question,
            'language' => $language,
            'useDbTools' => true,
        ]);

        return [
            'answer' => $result['response'] ?? 'Не удалось получить ответ.',
            'sources' => $result['sources'] ?? [],
        ];
    }
}
