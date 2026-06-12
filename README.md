# Notification Service

Микросервис для массовой рассылки SMS/Email уведомлений с приоритетами, дедупликацией и отслеживанием статусов.

## Технологии

- PHP 8.4 + Laravel 12
- PostgreSQL 15
- Redis (очереди и кэш)
- Docker / Docker Compose

## Быстрый старт

### 1. Клонирование репозитория
```bash
git clone https://github.com/AidarMukhitov/notification-service
cd notification-service
```

### 2. Запуск всех сервисов
```bash
docker-compose up -d
```

### 3. Выполнение миграций
```bash
docker exec -it notification_app php artisan migrate
```

### 4. Запуск тестов (опционально)
```bash
docker exec -it notification_app php artisan test
```

## API Endpoints

### 1. Массовая рассылка

```http
POST /api/broadcast
Content-Type: application/json
```

**Тело запроса:**
```json
{
    "channel": "sms",
    "message": "Текст сообщения",
    "recipient_ids": ["user1", "user2", "user3"],
    "priority": "transactional",
    "idempotency_key": "unique-key"
}
```

**Пример запроса (curl):**
```bash
curl -X POST http://localhost:8080/api/broadcast \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "sms",
    "message": "Ваш код: 123456",
    "recipient_ids": ["+79001234567", "+79007654321"],
    "priority": "transactional"
  }'
```

**Ответ:**
```json
{
    "batch_id": "uuid",
    "notification_ids": ["uuid1", "uuid2"],
    "total": 2,
    "status": "accepted"
}
```

### 2. История уведомлений

```http
GET /api/subscribers/{subscriberId}/notifications
```

**Пример:**
```bash
curl http://localhost:8080/api/subscribers/+79001234567/notifications
```

**Ответ:**
```json
[
    {
        "id": "uuid",
        "recipient_id": "+79001234567",
        "channel": "sms",
        "status": "delivered",
        "message": "Ваш код: 123456",
        "created_at": "2026-06-12T10:00:00.000000Z",
        "sent_at": "2026-06-12T10:00:01.000000Z",
        "delivered_at": "2026-06-12T10:00:04.000000Z"
    }
]
```

### 3. Health check

```http
GET /api/health
```

**Пример:**
```bash
curl http://localhost:8080/api/health
```

**Ответ:**
```json
{
    "status": "ok",
    "service": "Notification Service"
}
```

## Статусы уведомлений

| Статус | Описание |
|--------|----------|
| `queued` | В очереди, ожидает отправки |
| `sent` | Отправлено провайдеру |
| `delivered` | Доставлено получателю |
| `bounced` | Ошибка доставки (неверный номер/email) |

## Особенности реализации

- **Приоритеты**: транзакционные сообщения (коды доступа) идут в очередь `high`, маркетинговые в `low`
- **Дедубликация**: через `idempotency_key` с уникальным индексом в БД
- **Retry механизм**: 3 попытки с задержками 5, 15, 30 секунд
- **At-least-once**: подтверждение отправки только после успешного сохранения у провайдера
- **Provider mock**: имитирует реального провайдера с 90% успешных отправок

## Тестирование

```bash
# Запуск всех тестов
docker exec -it notification_app php artisan test

# Запуск конкретного теста
docker exec -it notification_app php artisan test --filter NotificationFlowTest
```

## Postman коллекция

В корне проекта есть файл `postman-collection.json`. Импортируйте его в Postman для удобного тестирования API.

## Остановка сервисов

```bash
docker-compose down
```

## Очистка всех данных (включая БД)

```bash
docker-compose down -v
```

## Примечание
В качестве брокера сообщений используется Redis (очереди с приоритетами). 
Redis выбран из-за простоты настройки и достаточной производительности для данного сценария. 
При необходимости легко заменить на RabbitMQ или Kafka.