# Docker Bake file for optimized parallel builds
# Usage: docker buildx bake --file docker-bake.hcl
# Or set COMPOSE_BAKE=1 and use: docker compose build

group "default" {
  targets = [
    "backend",
    "reverb",
    "queue-worker",
    "scheduler",
    "frontend",
  ]
}

target "backend" {
  context = "./backend"
  dockerfile = "docker/php/Dockerfile"
  target = "prod"
  tags = [
    "${REGISTRY:-local}/auction-backend:${TAG:-latest}",
    "${REGISTRY:-local}/auction-backend:${GIT_COMMIT:-unknown}",
  ]
  args = {
    UID = "1000"
    GID = "1000"
  }
  cache-from = [
    "type=registry,ref=${REGISTRY:-local}/auction-backend:cache",
    "type=gha",
  ]
  cache-to = [
    "type=registry,ref=${REGISTRY:-local}/auction-backend:cache,mode=max",
    "type=gha,mode=max",
  ]
  platforms = ["linux/amd64", "linux/arm64"]
  output = ["type=image,push=${PUSH:-false}"]
}

target "reverb" {
  inherits = ["backend"]
  target = "prod"
  tags = [
    "${REGISTRY:-local}/auction-reverb:${TAG:-latest}",
    "${REGISTRY:-local}/auction-reverb:${GIT_COMMIT:-unknown}",
  ]
}

target "queue-worker" {
  inherits = ["backend"]
  target = "prod"
  tags = [
    "${REGISTRY:-local}/auction-queue:${TAG:-latest}",
    "${REGISTRY:-local}/auction-queue:${GIT_COMMIT:-unknown}",
  ]
}

target "scheduler" {
  inherits = ["backend"]
  target = "prod"
  tags = [
    "${REGISTRY:-local}/auction-scheduler:${TAG:-latest}",
    "${REGISTRY:-local}/auction-scheduler:${GIT_COMMIT:-unknown}",
  ]
}

target "frontend" {
  context = "./frontend"
  dockerfile = "docker/frontend/Dockerfile"
  target = "production"
  tags = [
    "${REGISTRY:-local}/auction-frontend:${TAG:-latest}",
    "${REGISTRY:-local}/auction-frontend:${GIT_COMMIT:-unknown}",
  ]
  cache-from = [
    "type=registry,ref=${REGISTRY:-local}/auction-frontend:cache",
    "type=gha",
  ]
  cache-to = [
    "type=registry,ref=${REGISTRY:-local}/auction-frontend:cache,mode=max",
    "type=gha,mode=max",
  ]
  platforms = ["linux/amd64", "linux/arm64"]
  output = ["type=image,push=${PUSH:-false}"]
}

target "backend-dev" {
  inherits = ["backend"]
  target = "dev"
  tags = ["${REGISTRY:-local}/auction-backend:dev"]
  cache-from = ["type=gha"]
  cache-to = ["type=gha,mode=max"]
  platforms = ["linux/amd64"]
  output = ["type=image,push=false"]
}

target "frontend-dev" {
  inherits = ["frontend"]
  target = "builder"
  tags = ["${REGISTRY:-local}/auction-frontend:dev"]
  cache-from = ["type=gha"]
  cache-to = ["type=gha,mode=max"]
  platforms = ["linux/amd64"]
  output = ["type=image,push=false"]
}

variable "REGISTRY" {
  default = "local"
}

variable "TAG" {
  default = "latest"
}

variable "GIT_COMMIT" {
  default = "unknown"
}

variable "PUSH" {
  default = "false"
}