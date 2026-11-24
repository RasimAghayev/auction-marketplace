.PHONY: up down test lint
up:
	docker compose -f infra/compose.yaml up -d
down:
	docker compose -f infra/compose.yaml down
test:
	cd backend && php artisan test
lint:
	cd backend && ./vendor/bin/pint
