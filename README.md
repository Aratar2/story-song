# story-song

Маркетинговый лендинг для заказа персональных песен.

## Запуск через Docker Compose

1. Скопируйте пример и задайте при необходимости переменные окружения:
   ```bash
   export TELEGRAM_BOT_TOKEN="<ваш_токен>"
   export TELEGRAM_CHAT_ID="<ваш_chat_id>"
   # Необязательно: общее имя для сертификата
   export SSL_CERT_COMMON_NAME="songs.example.com"
   ```
2. Запустите контейнеры:
   ```bash
   docker compose up --build
   ```
3. Откройте сайт по адресу [https://localhost](https://localhost). Самоподписанный сертификат создаётся автоматически при старте.

Для остановки используйте `docker compose down`.
