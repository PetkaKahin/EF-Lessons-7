# EF Lesson 4

## Старт

Нужны Docker, Docker Compose и `make`.

```bash
make init
```

Команда соберет и запустит контейнеры, создаст `.env`, установит зависимости, сгенерирует `APP_KEY` и применит миграции.

После первого запуска:

```bash
make up
```

API будет доступно на:

```text
http://localhost:8080/api
```
