# Notification Service

Микросервис для массовой рассылки SMS/Email с приоритетами, дедупликацией и отслеживанием статусов.

## Запуск

```bash
docker-compose up -d
docker exec -it notification_app php artisan migrate
docker exec -it notification_app php artisan test
API
POST /api/broadcast - массовая рассылка

GET /api/subscribers/{id}/notifications - история

GET /api/health - проверка здоровья

Статусы
queued → sent → delivered / bounced