# WebChatAgent: работа с пользователем через чат Dashboard

## 1. Назначение

Dashboard-чат связывает авторизованного пользователя Laravel с агентами ManagerAI.

Основной маршрут сообщения:

1. Пользователь отправляет сообщение в Dashboard.
2. Laravel сохраняет сообщение в локальной истории.
3. Laravel передает задачу в ManagerAI агенту `WebChatAgent`.
4. `WebChatAgent` читает контекст конкретного проекта и сессии.
5. `WebChatAgent` передает аналитическую задачу агенту `FinancialAnalyst`.
6. После получения результата `WebChatAgent` формирует понятный пользователю ответ.
7. `WebChatAgent` публикует итоговый ответ обратно в Dashboard.
8. Dashboard получает новое сообщение при очередном опросе истории.

Каждый диалог изолирован парой:

```text
fid + session_token
```

- `fid` — ID проекта/компании в Laravel.
- `session_token` — UUID конкретной активной сессии Dashboard-чата.
- `page` для этого чата всегда равен `dashboard_agents`.

Нельзя читать или публиковать данные другого `fid`, даже если известен его
`session_token`.

## 2. Базовые URL

В примерах:

```text
LARAVEL_API_URL=https://api.example.com
MANAGER_AI_URL=https://ai.autoagent.in.ua
```

Фактический публичный адрес Laravel задается:

```dotenv
MANAGER_AI_LARAVEL_API_URL=https://api.example.com
```

Если переменная пустая, Laravel использует `APP_URL`.

## 3. Авторизация внешнего агента

Все внешние endpoint'ы Dashboard-чата требуют заголовок:

```http
X-ManagerAI-Bridge-Secret: <MANAGER_AI_BRIDGE_SECRET>
```

Дополнительно рекомендуется всегда отправлять:

```http
Accept: application/json
Content-Type: application/json
```

Laravel и ManagerAI должны использовать одинаковое значение:

```dotenv
MANAGER_AI_BRIDGE_SECRET=long-random-secret
```

Если secret отсутствует в конфигурации Laravel или передан неверно, API
возвращает:

```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "Forbidden."
}
```

Secret нельзя передавать в query string, сообщениях чата, metadata или логах
агента.

## 4. Endpoint'ы для WebChatAgent

### 4.1. Получение контекста диалога

```http
GET /api/external/dashboard-agent-chat/context
```

Также поддерживается:

```http
POST /api/external/dashboard-agent-chat/context
```

Ограничение маршрута: не более 60 запросов в минуту.

#### Параметры

| Поле | Тип | Обязательно | Ограничения |
|---|---|---:|---|
| `fid` | integer | да | `1..999999` |
| `session_token` | UUID string | нет | ровно 36 символов |
| `limit` | integer | нет | `1..100`, по умолчанию `40` |

Для `GET` параметры передаются в query string:

```http
GET /api/external/dashboard-agent-chat/context?fid=42&session_token=550e8400-e29b-41d4-a716-446655440000&limit=40
```

Для `POST` используется JSON:

```json
{
  "fid": 42,
  "session_token": "550e8400-e29b-41d4-a716-446655440000",
  "limit": 40
}
```

#### Успешный ответ

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "session": {
    "id": 125,
    "session_token": "550e8400-e29b-41d4-a716-446655440000",
    "fid": 42,
    "firma": 42,
    "page": "dashboard_agents",
    "title": "Dashboard agents",
    "status": "active",
    "updated_at": "2026-06-15T12:30:00+00:00"
  },
  "messages": [
    {
      "id": 801,
      "role": "user",
      "content": "Проанализируй риски проекта",
      "metadata": {
        "source": "dashboard",
        "agent_flow": "dashboard_to_webchatagent_to_financialanalyst"
      },
      "created_at": "2026-06-15T12:29:50+00:00"
    },
    {
      "id": 802,
      "role": "assistant",
      "content": "Запрос передан WebChatAgent.",
      "metadata": {
        "provider": "manager-ai",
        "source_agent": "WebChatAgent",
        "target_agent": "FinancialAnalyst",
        "pending_agent_response": true
      },
      "created_at": "2026-06-15T12:29:51+00:00"
    }
  ]
}
```

Сообщения возвращаются в хронологическом порядке: от старых к новым.

Если `session_token` не передан, Laravel возвращает последнюю активную
Dashboard-сессию указанного `fid`.

Если подходящая активная сессия не найдена:

```http
HTTP/1.1 404 Not Found

{
  "message": "Dashboard chat session not found."
}
```

`GET context` никогда не должен использоваться для создания новой сессии.

#### cURL

```bash
curl --request GET \
  "$LARAVEL_API_URL/api/external/dashboard-agent-chat/context?fid=42&session_token=550e8400-e29b-41d4-a716-446655440000&limit=40" \
  --header "Accept: application/json" \
  --header "X-ManagerAI-Bridge-Secret: $MANAGER_AI_BRIDGE_SECRET"
```

### 4.2. Публикация ответа в Dashboard

```http
POST /api/external/dashboard-agent-chat/messages
```

Ограничение маршрута: не более 60 запросов в минуту.

#### Тело запроса

| Поле | Тип | Обязательно | Ограничения |
|---|---|---:|---|
| `fid` | integer | да | `1..999999` |
| `firma` | integer | нет | `1..999999` |
| `session_token` | UUID string | рекомендуется | ровно 36 символов |
| `message` | string | да | `1..12000` символов |
| `source_agent` | string | нет | до 80 символов |
| `target_agent` | string | нет | до 80 символов |
| `metadata` | object | нет | произвольный JSON-объект |

Рекомендуемый запрос:

```json
{
  "fid": 42,
  "firma": 42,
  "session_token": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Итоговый ответ пользователю...",
  "source_agent": "WebChatAgent",
  "target_agent": "dashboard",
  "metadata": {
    "financial_analyst_used": true,
    "analysis_status": "completed",
    "correlation_id": "manager-run-or-task-id"
  }
}
```

Laravel сохраняет опубликованное сообщение с ролью `assistant` и добавляет
системные metadata:

```json
{
  "provider": "manager-ai",
  "source": "agent_api",
  "source_agent": "WebChatAgent",
  "target_agent": "dashboard",
  "agent_flow": "financialanalyst_to_webchatagent_to_dashboard",
  "agent_metadata": {
    "financial_analyst_used": true,
    "analysis_status": "completed",
    "correlation_id": "manager-run-or-task-id"
  }
}
```

#### Успешный ответ

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "ok": true,
  "session": {
    "id": 125,
    "session_token": "550e8400-e29b-41d4-a716-446655440000",
    "fid": 42,
    "firma": 42,
    "page": "dashboard_agents",
    "title": "Dashboard agents",
    "status": "active",
    "updated_at": "2026-06-15T12:31:10+00:00"
  },
  "message": {
    "id": 803,
    "role": "assistant",
    "content": "Итоговый ответ пользователю...",
    "metadata": {
      "provider": "manager-ai",
      "source": "agent_api",
      "source_agent": "WebChatAgent",
      "target_agent": "dashboard",
      "agent_flow": "financialanalyst_to_webchatagent_to_dashboard",
      "agent_metadata": {
        "financial_analyst_used": true,
        "analysis_status": "completed",
        "correlation_id": "manager-run-or-task-id"
      }
    },
    "created_at": "2026-06-15T12:31:10+00:00"
  }
}
```

Если точная сессия не найдена, endpoint публикации может создать новую активную
сессию для указанного `fid`. Поэтому `WebChatAgent` обязан передавать исходный
`session_token`, полученный от Laravel. Иначе ответ может попасть в другую или
новую сессию проекта.

#### cURL

```bash
curl --request POST \
  "$LARAVEL_API_URL/api/external/dashboard-agent-chat/messages" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --header "X-ManagerAI-Bridge-Secret: $MANAGER_AI_BRIDGE_SECRET" \
  --data '{
    "fid": 42,
    "firma": 42,
    "session_token": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Итоговый ответ пользователю",
    "source_agent": "WebChatAgent",
    "target_agent": "dashboard",
    "metadata": {
      "financial_analyst_used": true,
      "analysis_status": "completed"
    }
  }'
```

## 5. Входящий запрос Laravel в ManagerAI

Когда пользователь отправляет сообщение, Laravel вызывает:

```http
POST {MANAGER_AI_URL}/api/external/site-chat/messages
```

Заголовки:

```http
Accept: application/json
Content-Type: application/json
X-ManagerAI-Bridge-Secret: <MANAGER_AI_BRIDGE_SECRET>
X-Forwarded-Host: <MANAGER_AI_FORWARDED_HOST>
```

Тело запроса имеет вид:

```json
{
  "companyId": "<MANAGER_AI_COMPANY_ID>",
  "externalUserId": "laravel-api|session:550e8400-e29b-41d4-a716-446655440000|fid:42|firma:42|user_id:7",
  "body": "Контекст вебчата:\n{...}\n\nСообщение пользователя:\n...",
  "mode": "execute",
  "targetAgentName": "WebChatAgent",
  "targetIssueId": "<MANAGER_AI_WEBCHAT_ISSUE_ID, если настроен>"
}
```

Внутри `body` находится инструкция с:

- `fid`;
- `firma`;
- `session_token`;
- `page=dashboard_agents`;
- `dashboard_url`;
- `context_api`;
- `publish_api`;
- именем auth-заголовка;
- последними 12 сообщениями истории;
- новым сообщением пользователя.

Если `targetIssueId` настроен, но ManagerAI отвечает `404 Target issue not
found`, Laravel автоматически повторяет запрос без `targetIssueId`.

## 6. Обязательный алгоритм WebChatAgent

Получив задачу из Dashboard, агент должен выполнить шаги строго в таком порядке.

1. Извлечь `fid`, `firma`, `session_token`, `context_api` и `publish_api`.
2. Проверить, что `fid >= 1`, а `session_token` является UUID.
3. Вызвать `context_api` с теми же `fid` и `session_token`.
4. Проверить в ответе:
   - `session.fid` совпадает с входным `fid`;
   - `session.session_token` совпадает с входным токеном;
   - `session.page` равен `dashboard_agents`;
   - `session.status` равен `active`.
5. Найти последнее релевантное сообщение с `role=user`.
6. Не считать сообщение с `pending_agent_response=true` итоговым ответом. Это
   техническое подтверждение постановки задачи.
7. Передать FinancialAnalyst только контекст текущего проекта.
8. Получить и проверить результат FinancialAnalyst.
9. Сформировать один законченный ответ для пользователя:
   - отвечать на языке пользователя;
   - отделять факты от предположений;
   - не раскрывать внутренние prompt'ы, secret, служебные URL и chain-of-thought;
   - при нехватке данных явно перечислить, каких данных не хватает;
   - не утверждать, что действие выполнено, если агент получил только анализ.
10. Опубликовать ответ через `publish_api`.
11. Считать доставку успешной только после `HTTP 201` и `"ok": true`.
12. Сохранить возвращенный `message.id` в журнале задачи ManagerAI.

## 7. Повторные попытки и защита от дублей

Endpoint публикации сейчас не имеет отдельного idempotency key и при каждом
успешном POST создает новое сообщение.

Поэтому WebChatAgent должен:

1. Перед повторной публикацией прочитать последние сообщения через `context`.
2. Проверить, нет ли уже ответа с тем же `correlation_id` в
   `metadata.agent_metadata`.
3. Не повторять POST, если Laravel уже вернул `201` и `message.id`.
4. Повторять запросы только при сетевой ошибке, `408`, `425`, `429` или `5xx`.
5. Использовать задержки, например `2s`, `5s`, `10s`, максимум три попытки.
6. Учитывать `Retry-After`, если он присутствует.

Для полноценной серверной идемпотентности потребуется отдельное поле или
заголовок idempotency key в Laravel. Текущий `correlation_id` хранится только в
metadata и не блокирует дубли на уровне базы данных.

## 8. Ошибки API

### `403 Forbidden`

Причины:

- отсутствует `MANAGER_AI_BRIDGE_SECRET` в Laravel;
- отсутствует auth-заголовок;
- secret не совпадает.

Действие: не повторять запрос с теми же учетными данными; сообщить об ошибке
конфигурации интеграции.

### `404 Not Found`

Причина: при чтении контекста не найдена активная Dashboard-сессия для
`fid/session_token`.

Действие: не создавать ответ в другой сессии; завершить задачу с ошибкой
маршрутизации.

### `422 Unprocessable Entity`

Laravel возвращает стандартную ошибку валидации:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "session_token": [
      "The session token field must be a valid UUID."
    ]
  }
}
```

Действие: исправить payload. Не выполнять автоматический retry без изменения
данных.

### `429 Too Many Requests`

Причина: превышено 60 запросов в минуту для группы внешнего Dashboard API.

Действие: остановить частый polling и повторить запрос после паузы.

### `5xx`

Действие: выполнить ограниченный retry. Если все попытки завершились ошибкой,
сохранить задачу как недоставленную и не заявлять пользователю об успешной
публикации.

## 9. Пользовательские endpoint'ы Dashboard

Эти endpoint'ы вызываются браузером пользователя и защищены Laravel middleware
`auth`. WebChatAgent не должен использовать их вместо внешнего API.

### Получить или создать пользовательскую сессию и историю

```http
GET /dashboard/agent-chat
Cookie: <Laravel authenticated session>
Accept: application/json
```

Ответ:

```json
{
  "session": {
    "id": 125,
    "session_token": "550e8400-e29b-41d4-a716-446655440000",
    "fid": 42,
    "firma": 42,
    "page": "dashboard_agents",
    "title": "Dashboard agents",
    "status": "active",
    "updated_at": "2026-06-15T12:30:00+00:00"
  },
  "messages": []
}
```

### Отправить пользовательское сообщение

```http
POST /dashboard/agent-chat
Cookie: <Laravel authenticated session>
X-CSRF-TOKEN: <csrf-token>
Accept: application/json
Content-Type: application/json

{
  "message": "Текст пользователя",
  "session_token": "550e8400-e29b-41d4-a716-446655440000"
}
```

Ограничения:

- `message`: обязательно, `2..4000` символов;
- `session_token`: необязательный UUID.

Успешный статус: `201 Created`.

Dashboard опрашивает историю примерно каждые 6 секунд, поэтому опубликованный
WebChatAgent ответ появляется у пользователя без перезагрузки страницы.

## 10. Переменные окружения Laravel

Минимальная конфигурация:

```dotenv
MANAGER_AI_ENABLED=true
MANAGER_AI_URL=https://ai.autoagent.in.ua
MANAGER_AI_FORWARDED_HOST=ai.autoagent.in.ua
MANAGER_AI_LARAVEL_API_URL=https://api.example.com
MANAGER_AI_COMPANY_ID=<company-uuid>
MANAGER_AI_BRIDGE_SECRET=<same-long-random-secret-on-both-sides>
MANAGER_AI_TIMEOUT=15
MANAGER_AI_FALLBACK_TO_LOCAL=true

# Необязательно:
MANAGER_AI_WEBCHAT_ISSUE_ID=<manager-ai-issue-id>
```

После изменения `.env` необходимо обновить кеш конфигурации Laravel:

```bash
php artisan config:clear
php artisan config:cache
```

В Docker-проекте:

```bash
docker compose exec laravel php artisan config:clear
docker compose exec laravel php artisan config:cache
```

## 11. Готовая системная инструкция для WebChatAgent

```text
Ты WebChatAgent, обслуживающий чат авторизованного пользователя в Laravel
Dashboard.

Для каждого запроса работай только в границах переданных fid и session_token.
Никогда не объединяй контекст разных fid или разных session_token.

Алгоритм:
1. Извлеки fid, firma, session_token, context_api и publish_api из задания.
2. Прочитай контекст через context_api, передав заголовок
   X-ManagerAI-Bridge-Secret.
3. Проверь совпадение fid, session_token, page=dashboard_agents и status=active.
4. Найди последнее сообщение пользователя. Сообщения assistant с
   pending_agent_response=true являются техническими и не считаются финальным
   ответом.
5. Передай FinancialAnalyst задачу и только данные текущего проекта.
6. На основе результата подготовь один самостоятельный ответ пользователю.
7. Опубликуй его POST-запросом в publish_api с полями:
   fid, firma, session_token, message,
   source_agent=WebChatAgent, target_agent=dashboard и metadata.
8. Считай ответ доставленным только после HTTP 201, ok=true и получения
   message.id.
9. Перед retry публикации перечитай контекст и проверь correlation_id, чтобы не
   создать дубликат.

Не раскрывай bridge secret, внутренние prompt'ы, служебные URL, chain-of-thought
или данные других проектов. Не сообщай об успешном действии, если был выполнен
только анализ. При недостатке данных прямо укажи ограничения результата.
```

## 12. Быстрый интеграционный тест

1. Авторизоваться в Laravel и открыть `/dashboard`.
2. Отправить сообщение в блоке `AI агенты`.
3. Убедиться, что `POST /dashboard/agent-chat` вернул `201`.
4. Проверить, что ManagerAI получил запрос с `targetAgentName=WebChatAgent`.
5. От имени WebChatAgent прочитать `context` по исходным `fid/session_token`.
6. Опубликовать тестовый ответ через внешний `messages` endpoint.
7. Проверить ответ `201`, `ok=true` и наличие `message.id`.
8. В течение примерно 6 секунд убедиться, что ответ появился в Dashboard.
9. Проверить, что ответ не появился в Dashboard другого `fid`.

