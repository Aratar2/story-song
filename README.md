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

## Развёртывание на Fly.io

1. Установите [Fly CLI](https://fly.io/docs/hands-on/install-flyctl/) и выполните аутентификацию:
   ```bash
   flyctl auth login
   ```
2. Обновите название приложения в `fly.toml` (строка `app = "story-song"`). Значение должно быть уникальным в Fly.io.
3. Создайте приложение без деплоя, чтобы зарегистрировать имя и проверить конфигурацию:
   ```bash
   flyctl launch --no-deploy
   ```
4. Задайте секреты с Telegram-данными (пустые значения можно пропустить, если отправка не нужна):
   ```bash
   flyctl secrets set TELEGRAM_BOT_TOKEN=<ваш_токен> TELEGRAM_CHAT_ID=<ваш_chat_id>
   ```
5. Запустите сборку и деплой:
   ```bash
   flyctl deploy
   ```
6. Откройте сайт командой:
   ```bash
   flyctl open
   ```

Fly автоматически выдаёт TLS-сертификат и проксирует запросы к внутреннему HTTP-сервису на порту 8080.
