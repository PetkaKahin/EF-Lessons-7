#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ef-lessons-7}"
BRANCH="${BRANCH:-main}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-http://localhost:8080/health}"

SUDO=""
if [ "$(id -u)" -ne 0 ]; then
  SUDO="sudo"
fi

install_packages() {
  if ! command -v apt-get >/dev/null 2>&1; then
    echo "FAIL: автоустановка поддерживает Debian/Ubuntu. Поставь зависимости вручную." >&2
    exit 1
  fi

  $SUDO apt-get update
  $SUDO apt-get install -y "$@"
}

echo ">> 0. Проверяем зависимости"

if ! command -v curl >/dev/null 2>&1; then
  install_packages curl ca-certificates
fi

if ! command -v git >/dev/null 2>&1; then
  install_packages git
fi

if ! command -v make >/dev/null 2>&1; then
  install_packages make
fi

if ! command -v docker >/dev/null 2>&1; then
  curl -fsSL https://get.docker.com | $SUDO sh
fi

if docker info >/dev/null 2>&1; then
  COMPOSE="docker compose"
else
  COMPOSE="$SUDO docker compose"
fi

if ! $COMPOSE version >/dev/null 2>&1; then
  install_packages docker-compose-plugin
fi

echo ">> 1. Переходим в проект"
cd "$APP_DIR"

if [ ! -f .env ]; then
  echo "FAIL: нет .env на сервере. Скопируй .env.prod.example в .env и заполни секреты" >&2
  exit 1
fi

echo ">> 2. Забираем свежий код"
git fetch --prune origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo ">> 3. Запускаем деплой через Makefile"
make deploy COMPOSE="$COMPOSE" HEALTHCHECK_URL="$HEALTHCHECK_URL"

echo "OK: деплой завершён"
