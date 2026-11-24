.PHONY: up down test lint
up:
	docker compose -f infra/compose.yaml up -d
down:
	docker compose -f infra/compose.yaml down
test:
	docker compose -f infra/compose.yaml exec backend php artisan test
lint:
	docker compose -f infra/compose.yaml exec backend ./vendor/bin/pint
