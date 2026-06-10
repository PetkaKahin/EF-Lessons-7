COMPOSE_ENV=$(if $(wildcard docker/.env),--env-file docker/.env,)
COMPOSE=docker compose $(COMPOSE_ENV)

.PHONY: init up uo down build restart ps logs app bash composer artisan migrate seed-test-data test

init:
	$(COMPOSE) up -d --build
	$(COMPOSE) exec php sh -lc "if [ -f .env.example ] && [ ! -f .env ]; then cp .env.example .env; fi"
	$(COMPOSE) exec php sh -lc "if [ -f .env ]; then php docker/bin/configure-laravel-env.php .env; fi"
	$(COMPOSE) exec php sh -lc "if [ -f composer.json ]; then composer install; fi"
	$(COMPOSE) exec php sh -lc "if [ -f artisan ]; then php artisan key:generate --ansi; fi"
	$(COMPOSE) exec php sh -lc "if [ -f artisan ]; then php artisan migrate --force; fi"

up:
	$(COMPOSE) up -d

uo: up

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

restart:
	$(COMPOSE) restart

ps:
	$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

app:
	$(COMPOSE) exec php sh

bash:
	$(COMPOSE) exec php bash

composer:
	$(COMPOSE) exec php composer install

artisan:
	$(COMPOSE) exec php php artisan

migrate:
	$(COMPOSE) exec php php artisan migrate

seed-test-data:
	$(COMPOSE) exec php php artisan db:seed

test:
	$(COMPOSE) exec php php artisan test
