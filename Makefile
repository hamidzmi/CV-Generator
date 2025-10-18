SHELL := /bin/bash

.PHONY: install up down logs schema-sync test tests

install:
	docker compose run --rm symfony composer install

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f symfony weaviate ollama

schema-sync:
	docker compose exec symfony bin/console app:indexing:sync-schema

test tests:
	docker compose run --rm -e APP_ENV=test -e APP_DEBUG=0 symfony bash -lc 'rm -rf var/cache/test && ./vendor/bin/simple-phpunit'
