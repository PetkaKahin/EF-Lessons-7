# EF Lessons 7

## Старт

Нужны Docker, Docker Compose и `make`

```bash
make init
```

По умолчанию это dev-режим. Команда скопирует `.env.dev.example` в `.env`, соберет и запустит контейнеры, установит зависимости, сгенерирует `APP_KEY` и применит миграции

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
```

## Деплой на VPS по SSH

Один раз на сервере: склонировать репозиторий в рабочую директорию и создать production `.env` из `.env.prod.example`. Секреты (`APP_KEY`, `DB_PASSWORD`, пароли внешних сервисов) хранить только в `.env` на сервере и не коммитьте их

Пример запуска по SSH с локальной машины:

```bash
ssh deploy@123.12.123.12 'APP_DIR=/var/www/ef-lessons-7 BRANCH=main bash -s' < deploy/deploy.sh
```

`deploy/deploy.sh` выполняется на VPS: ставит `git`, `curl`, Docker и `make` на Debian/Ubuntu, если их нет, обновляет код и запускает одну команду:

```bash
make deploy
```

Что делает скрипт:

1. устанавливает недостающие `git`, `curl`, Docker и `make`;
2. переходит в директорию проекта;
3. проверяет, что на сервере есть `.env`;
4. выполняет `git fetch`, переключение на ветку и `git pull --ff-only`;
5. запускает `make deploy`.

Что делает `make deploy`:

1. проверяет наличие `.env`;
2. собирает и обновляет контейнеры через `docker compose up -d --build`;
3. устанавливает production-зависимости Composer;
4. запускает миграции;
5. выполняет healthcheck `http://127.0.0.1:8080/health`.
