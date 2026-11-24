# Auction Marketplace - Docker Setup

Production-ready Docker Compose configuration using modern Compose v2/v5 features.

## Quick Start

### Development (with hot reload)
```bash
# Start all services with watch mode
docker compose up --watch

# Or run watch separately (keeps logs clean)
docker compose up -d && docker compose watch

# Start with debug tools (Mongo Express, Redis Commander)
docker compose --profile debug up -d
```

### Production
```bash
# Build and deploy production stack
docker compose -f compose.yaml -f compose.prod.yaml up -d

# With specific tag
TAG=v1.2.3 docker compose -f compose.yaml -f compose.prod.yaml up -d

# Using Bake for parallel builds
COMPOSE_BAKE=1 docker compose -f compose.yaml -f compose.prod.yaml build
# Or directly with Buildx
docker buildx bake --file docker-bake.hcl
```

## Compose File Structure

| File | Purpose | Auto-loaded |
|------|---------|-------------|
| `compose.yaml` | Base production configuration | Yes |
| `compose.override.yaml` | Development overrides (watch, bind mounts, debug ports) | Yes |
| `compose.prod.yaml` | Production hardening (read-only, resource limits, security) | No (explicit) |
| `docker-bake.hcl` | Buildx Bake configuration for parallel builds | N/A |

## Profiles

Services are organized by profiles for selective startup:

```bash
# Core services only (default)
docker compose up -d

# With debug tools
docker compose --profile debug up -d

# With monitoring stack (Prometheus, Grafana, Loki)
docker compose --profile monitoring up -d

# With testing tools
docker compose --profile testing up -d

# Multiple profiles
docker compose --profile debug --profile monitoring up -d

# Run profiled service directly (with dependencies)
docker compose run --rm mongo-express
```

## Key Features

### Watch Mode (Development)
Automatic file synchronization and rebuilds:
- **sync**: Copy changed files to container (PHP, Vue)
- **sync+restart**: Sync then restart container (config files)
- **rebuild**: Full image rebuild (composer.json, package.json)

```bash
docker compose up --watch
```

### Health Checks & Dependency Ordering
All stateful services have health checks. Dependencies use `condition: service_healthy`:
```yaml
depends_on:
  mongodb:
    condition: service_healthy
    restart: true  # Restart dependent if mongodb restarts
```

### Resource Limits
Production deployments include CPU/memory limits:
```yaml
deploy:
  resources:
    limits:
      memory: 1G
      cpus: "2.0"
    reservations:
      memory: 512M
      cpus: "1.0"
```

### Security Hardening (Production)
- `read_only: true` root filesystem
- `cap_drop: [ALL]` - drop all capabilities
- `security_opt: [no-new-privileges:true]`
- Non-root users
- Internal networks for databases

### Secrets Management
Secrets stored in `./secrets/` (gitignored):
```yaml
secrets:
  mongodb_root_password:
    file: ./secrets/mongodb_root_password.txt
```
Referenced in services:
```yaml
environment:
  - MONGODB_ROOT_PASSWORD_FILE=/run/secrets/mongodb_root_password
```

### Network Segmentation
- `frontend-network`: Public-facing (nginx, frontend, monitoring UI)
- `backend-internal`: Private (database, redis, workers) - `internal: true`

### Logging
JSON file driver with rotation:
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "5"
```

## Commands Reference

### Development
```bash
# Start with watch mode
docker compose up --watch

# View logs
docker compose logs -f backend

# Run artisan commands
docker compose exec backend php artisan migrate
docker compose exec backend php artisan queue:work

# Run tests
docker compose --profile testing run --rm test-runner

# Access container shell
docker compose exec backend bash
```

### Production
```bash
# Deploy
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Scale workers
docker compose -f compose.yaml -f compose.prod.yaml up -d --scale queue-worker=5

# Rolling update
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d --remove-orphans

# Backup volumes
docker run --rm -v auction-mongodb-data:/data -v $(pwd)/backups:/backup alpine tar czf /backup/mongodb-$(date +%F).tar.gz /data
```

### Maintenance
```bash
# View merged config
docker compose config

# View merged production config
docker compose -f compose.yaml -f compose.prod.yaml config

# Clean up
docker compose down -v --remove-orphans

# Prune unused
docker system prune -a --volumes
```

## Environment Variables

Copy `.env.example` to `.env` and customize:
```bash
cp .env.example .env
```

Key variables:
- `APP_ENV` - production/development/testing
- `APP_DEBUG` - true/false
- `REGISTRY` - Docker registry (ghcr.io/your-org)
- `TAG` - Image tag (latest, v1.0.0, git-sha)
- `PUSH` - Push images after build (true/false)

## Secrets Generation

Generate strong passwords:
```bash
# MongoDB root password
openssl rand -base64 32 > secrets/mongodb_root_password.txt

# Redis password
openssl rand -base64 32 > secrets/redis_password.txt

# Grafana admin
openssl rand -base64 16 > secrets/grafana_admin_password.txt
```

## Monitoring Stack (Profile: monitoring)

Access URLs when monitoring profile is active:
- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3000 (admin / secret)
- **Loki**: http://localhost:3100

## Debug Tools (Profile: debug)

- **Mongo Express**: http://localhost:8081
- **Redis Commander**: http://localhost:8082

## Architecture

```
┌─────────────────┐     ┌──────────────────┐
│   Internet      │────▶│    Nginx (SSL)   │
└─────────────────┘     └────────┬─────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
             ┌──────────┐ ┌────────────┐ ┌──────────┐
             │ Frontend │ │  Backend   │ │ Reverb   │
             │  (Vue)   │ │  (Laravel) │ │ (WS)     │
             └──────────┘ └─────┬──────┘ └──────────┘
                                │
                    ┌───────────┼───────────┐
                    ▼           ▼           ▼
               ┌────────┐ ┌──────────┐ ┌──────────┐
               │ MongoDB│ │  Redis   │ │ Queue    │
               │ (8.0)  │ │  (8.0)   │ │ Workers  │
               └────────┘ └──────────┘ └──────────┘
```

## Migration from docker-compose.yml

The legacy `docker-compose.yml` is replaced by:
- `compose.yaml` (base)
- `compose.override.yaml` (dev)
- `compose.prod.yaml` (production)

Run `docker compose config` to verify the merged configuration.

## Troubleshooting

### Services won't start
```bash
# Check health status
docker compose ps

# View service logs
docker compose logs mongodb

# Check health check details
docker inspect auction-mongodb --format '{{json .State.Health}}'
```

### Watch mode not syncing
- Ensure service has `build:` section (not `image:` only)
- Check `develop.watch.path` matches actual directory
- Verify `read_only: false` in override for sync targets

### Permission issues
```bash
# Fix storage permissions
docker compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### Port conflicts
Change host ports in compose files or use `docker compose --project-name` for isolation.

## License

MIT