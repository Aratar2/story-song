# story-song

Маркетинговый лендинг для заказа персональных песен.

## Запуск через Docker Compose

1. Создайте рядом с `docker-compose.yml` файл `.env` и добавьте в него нужные параметры (файл не попадёт в репозиторий, он уже добавлен в `.gitignore`):
   ```dotenv
   TELEGRAM_BOT_TOKEN=<ваш_токен>
   TELEGRAM_CHAT_ID=<ваш_chat_id>
   # Необязательно: общее имя для сертификата
   SSL_CERT_COMMON_NAME=songs.example.com
   ```
2. Запустите контейнеры:
   ```bash
   docker compose up --build
   ```
3. Откройте сайт по адресу [https://localhost](https://localhost). Самоподписанный сертификат создаётся автоматически при старте.

Для остановки используйте `docker compose down`.
