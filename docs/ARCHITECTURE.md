# Architecture

## Infra layout

- `infra/docker/` - Dockerfiles, compose services, and container configs (nginx inside docker/nginx for the reverse proxy container)
- `infra/nginx/` - Standalone nginx config files used by host/edge (nginx-frontend.conf, frontend-nginx.conf)
- `infra/scripts/` - run-tests, setup scripts
- `infra/ci-cd/` - CI/CD definitions

## Nginx difference

`infra/docker/nginx/` configures nginx **inside** the docker container (reverse proxy container).
`infra/nginx/` holds standalone nginx configs applied on host/edge.

```mermaid
flowchart TD
    A[Client] --> B[Edge Nginx infra/nginx]
    B --> C[Docker Reverse Proxy infra/docker/nginx]
    C --> D[Backend container]
    C --> E[Frontend container]
```

## Branch flow

```mermaid
flowchart LR
    Bots[Dependency Bots] --> Preview[preview]
    Preview -->|GREEN PRs| Match[Version Match]
    Match --> Agg[Aggregate Branch]
    Agg -->|GREEN| Test[test]
    Test -->|PR| Main[main]
```

## Layer separation

```mermaid
flowchart TB
    Infra[infra/] -->|manages| Docker[Docker/CI/Nginx]
    Backend[backend/] -->|manages| API[API/Services/Models]
    Frontend[frontend/] -->|manages| UI[Components/Pages/State]
```
