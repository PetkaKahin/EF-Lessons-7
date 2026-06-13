COMPOSE=docker compose
APP_SERVICE=app
HEALTHCHECK_URL?=http://127.0.0.1:8080/health

ifeq ($(OS),Windows_NT)
COPY_FILE=powershell -NoProfile -ExecutionPolicy Bypass -Command "Copy-Item -LiteralPath '$(1)' -Destination '$(2)' -Force"
else
COPY_FILE=cp "$(1)" "$(2)"
endif

.PHONY: init init-dev init-prod env-dev env-prod up up-dev up-prod uo down build build-dev build-prod restart ps logs app bash composer composer-prod artisan migrate seed seed-test-data test lint analyse optimize clear deploy

init: init-dev

init-dev: env-dev
	$(COMPOSE) up -d --build
	$(COMPOSE) exec $(APP_SERVICE) composer install
	$(COMPOSE) exec $(APP_SERVICE) php artisan key:generate --ansi --force
	$(COMPOSE) exec $(APP_SERVICE) php artisan config:clear
	$(COMPOSE) exec $(APP_SERVICE) php artisan migrate --force

init-prod: env-prod
	$(COMPOSE) up -d --build
	$(COMPOSE) exec $(APP_SERVICE) composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
	$(COMPOSE) exec $(APP_SERVICE) php artisan key:generate --ansi --force
	$(COMPOSE) exec $(APP_SERVICE) php artisan config:clear
	$(COMPOSE) exec $(APP_SERVICE) php artisan migrate --force
	$(COMPOSE) exec $(APP_SERVICE) php artisan optimize:clear
	$(COMPOSE) exec $(APP_SERVICE) php artisan optimize

env-dev:
	$(call COPY_FILE,.env.dev.example,.env)

env-prod:
	$(call COPY_FILE,.env.prod.example,.env)

up:
	$(COMPOSE) up -d

up-dev: env-dev up

up-prod: env-prod up

uo: up

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

build-dev: env-dev build

build-prod: env-prod build

restart:
	$(COMPOSE) restart

ps:
	$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

app:
	$(COMPOSE) exec $(APP_SERVICE) sh

bash:
	$(COMPOSE) exec $(APP_SERVICE) bash

composer:
	$(COMPOSE) exec $(APP_SERVICE) composer install

composer-prod:
	$(COMPOSE) exec $(APP_SERVICE) composer install --no-dev --prefer-dist --optimize-autoloader

artisan:
	$(COMPOSE) exec $(APP_SERVICE) php artisan

migrate:
	$(COMPOSE) exec $(APP_SERVICE) php artisan migrate --force

seed:
	$(COMPOSE) exec $(APP_SERVICE) php artisan db:seed

seed-test-data: seed

test:
	$(COMPOSE) exec $(APP_SERVICE) php artisan config:clear
	$(COMPOSE) exec $(APP_SERVICE) php artisan test

lint:
	$(COMPOSE) exec $(APP_SERVICE) ./vendor/bin/pint --test

analyse:
	$(COMPOSE) exec $(APP_SERVICE) ./vendor/bin/phpstan analyse

optimize:
	$(COMPOSE) exec $(APP_SERVICE) php artisan optimize

clear:
	$(COMPOSE) exec $(APP_SERVICE) php artisan optimize:clear

deploy:
	@test -f .env || (echo "На сервере нет .env. Создай его из .env.prod.example и заполни секреты." && exit 1)
	$(COMPOSE) up -d --build
	$(COMPOSE) exec -T $(APP_SERVICE) composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
	$(COMPOSE) exec -T $(APP_SERVICE) php artisan optimize:clear
	$(COMPOSE) exec -T $(APP_SERVICE) php artisan migrate --force
	$(COMPOSE) exec -T $(APP_SERVICE) php artisan optimize
	curl -fsS "$(HEALTHCHECK_URL)" >/dev/null
	@echo "Deploy finished"
