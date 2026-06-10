# EF Lessons 7

## Старт

Нужны Docker, Docker Compose и `make`.

```bash
make init
```

По умолчанию это dev-режим. Команда скопирует `.env.dev.example` в `.env`, соберет и запустит контейнеры, установит зависимости, сгенерирует `APP_KEY` и применит миграции.

Для явного выбора окружения:

```bash
make init-dev
make init-prod
```

После первого запуска:

```bash
make up
```

API будет доступно на:

```text
http://localhost:8080/api
```

Миграции отдельно:

```bash
make migrate
# или
docker compose exec app php artisan migrate --force
```
