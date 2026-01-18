.PHONY: help start stop restart logs build clean db-reset

help:
	@echo "Farkle Ten - Docker Commands"
	@echo "============================="
	@echo "make start      - Start all services"
	@echo "make stop       - Stop all services"
	@echo "make restart    - Restart all services"
	@echo "make logs       - View logs (Ctrl+C to exit)"
	@echo "make build      - Rebuild containers"
	@echo "make clean      - Stop and remove all containers and volumes"
	@echo "make db-reset   - Reset database (WARNING: deletes all data)"
	@echo "make shell      - Access web container shell"
	@echo "make db-shell   - Access MySQL CLI"

start:
	docker-compose up -d
	@echo ""
	@echo "✅ Farkle Ten is starting..."
	@echo "🌐 Game: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"
	@echo "👤 Test user: testuser / test123"
	@echo ""
	@echo "Run 'make logs' to view logs"

stop:
	docker-compose down

restart:
	docker-compose restart

logs:
	docker-compose logs -f

build:
	docker-compose up -d --build

clean:
	docker-compose down -v
	@echo "All containers and volumes removed"

db-reset:
	@echo "⚠️  WARNING: This will delete ALL data!"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v; \
		docker-compose up -d; \
		echo "Database reset complete"; \
	fi

shell:
	docker-compose exec web bash

db-shell:
	docker-compose exec db mysql -u farkle_user -pfarkle_pass mikeschm_db
