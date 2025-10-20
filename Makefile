SHELL := /bin/bash

.PHONY: install up down logs schema-sync test tests

install:
	docker compose run --rm symfony composer install

OLLAMA_EMBED_MODEL := $(shell sed -n 's/^OLLAMA_EMBED_MODEL=//p' backend/.env | tail -n 1)
OLLAMA_CV_MODEL := $(shell sed -n 's/^OLLAMA_CV_MODEL=//p' backend/.env | tail -n 1)

ifeq ($(strip $(OLLAMA_EMBED_MODEL)),)
	OLLAMA_EMBED_MODEL := nomic-embed-text:latest
endif

ifeq ($(strip $(OLLAMA_CV_MODEL)),)
	OLLAMA_CV_MODEL := llama3:latest
endif

up:
	docker compose up -d
	docker compose exec ollama ollama pull $(OLLAMA_EMBED_MODEL)
	docker compose exec ollama ollama pull $(OLLAMA_CV_MODEL)

down:
	docker compose down

logs:
	docker compose logs -f symfony weaviate ollama

schema-sync:
	docker compose exec symfony bin/console app:indexing:sync-schema

test tests:
	docker compose run --rm -e APP_ENV=test -e APP_DEBUG=0 symfony bash -lc 'rm -rf var/cache/test && ./vendor/bin/simple-phpunit'
