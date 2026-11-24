# Architecture

## High-level
Auction marketplace: Laravel 13 backend (MongoDB), Vue 3 frontend, Nginx reverse proxy, Docker Compose infra.

`mermaid
flowchart LR
    Client[Browser] --> Nginx[infra/docker/nginx]
    Nginx --> FE[frontend]
    Nginx --> BE[backend Laravel]
    BE --> Mongo[(MongoDB)]
    BE --> Redis[(Redis)]
`

## Backend
- Entry: backend/public/index.php, bootstrap/app.php
- Routes: backend/routes/api.php (prefix /api/v1)
- Controllers: backend/app/Http/Controllers/Api/V1/
- Models: backend/app/Models/ (MongoDB\Laravel)
- Auth: Sanctum custom PersonalAccessToken (backend/app/Models/PersonalAccessToken.php)

## Frontend
- Entry: frontend/src/main.ts
- Router: frontend/src/router/index.ts
- Stores: frontend/src/stores/
- Views: frontend/src/views/

## Infra
- Compose: infra/compose.yaml
- Docker: infra/docker/
- Nginx: infra/docker/nginx (container) + infra/nginx (host/edge)
- CI: .github/workflows/ci.yml

## Data flow (product create)
Frontend service -> POST /api/v1/products -> ProductController -> Product model -> products collection.

## Verified paths
- backend/app/Http/Controllers/Api/V1/ProductController.php
- backend/app/Models/Product.php
- frontend/src/views/HomeView.vue
