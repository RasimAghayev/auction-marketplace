#!/bin/bash
# Test runner script for auction marketplace
# Runs all test suites in Docker containers

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "=========================================="
echo "Auction Marketplace Test Runner"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default options
RUN_UNIT=true
RUN_FEATURE=true
RUN_BDD=true
RUN_ATDD=true
RUN_FRONTEND_UNIT=true
RUN_E2E=true
RUN_API=true
BUILD=false
CLEANUP=true

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --unit)
      RUN_UNIT=true
      RUN_FEATURE=false
      RUN_BDD=false
      RUN_ATDD=false
      RUN_FRONTEND_UNIT=false
      RUN_E2E=false
      RUN_API=false
      shift
      ;;
    --feature)
      RUN_FEATURE=true
      RUN_UNIT=false
      RUN_BDD=false
      RUN_ATDD=false
      RUN_FRONTEND_UNIT=false
      RUN_E2E=false
      RUN_API=false
      shift
      ;;
    --bdd)
      RUN_BDD=true
      RUN_UNIT=false
      RUN_FEATURE=false
      RUN_ATDD=false
      RUN_FRONTEND_UNIT=false
      RUN_E2E=false
      RUN_API=false
      shift
      ;;
    --atdd)
      RUN_ATDD=true
      RUN_UNIT=false
      RUN_FEATURE=false
      RUN_BDD=false
      RUN_FRONTEND_UNIT=false
      RUN_E2E=false
      RUN_API=false
      shift
      ;;
    --frontend)
      RUN_FRONTEND_UNIT=true
      RUN_UNIT=false
      RUN_FEATURE=false
      RUN_BDD=false
      RUN_ATDD=false
      RUN_E2E=false
      RUN_API=false
      shift
      ;;
    --e2e)
      RUN_E2E=true
      RUN_UNIT=false
      RUN_FEATURE=false
      RUN_BDD=false
      RUN_ATDD=false
      RUN_FRONTEND_UNIT=false
      RUN_API=false
      shift
      ;;
    --api)
      RUN_API=true
      RUN_UNIT=false
      RUN_FEATURE=false
      RUN_BDD=false
      RUN_ATDD=false
      RUN_FRONTEND_UNIT=false
      RUN_E2E=false
      shift
      ;;
    --all)
      RUN_UNIT=true
      RUN_FEATURE=true
      RUN_BDD=true
      RUN_ATDD=true
      RUN_FRONTEND_UNIT=true
      RUN_E2E=true
      RUN_API=true
      shift
      ;;
    --build)
      BUILD=true
      shift
      ;;
    --no-cleanup)
      CLEANUP=false
      shift
      ;;
    --help)
      echo "Usage: $0 [options]"
      echo "Options:"
      echo "  --unit        Run backend unit tests only"
      echo "  --feature     Run backend feature tests only"
      echo "  --bdd         Run BDD tests only"
      echo "  --atdd        Run ATDD tests only"
      echo "  --frontend    Run frontend unit tests only"
      echo "  --e2e         Run Playwright E2E tests only"
      echo "  --api         Run Playwright API tests only"
      echo "  --all         Run all test suites (default)"
      echo "  --build       Build Docker images before running tests"
      echo "  --no-cleanup  Don't stop containers after tests"
      echo "  --help        Show this help message"
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Function to run a test suite
run_test_suite() {
  local name=$1
  local profile=$2
  local service=$3
  local command=$4

  echo -e "\n${YELLOW}Running $name...${NC}"
  echo "Profile: $profile, Service: $service"

  if [ "$BUILD" = true ]; then
    docker compose --profile "$profile" build "$service"
  fi

  local result=0
  docker compose --profile "$profile" run --rm "$service" $command || result=$?

  if [ $result -eq 0 ]; then
    echo -e "${GREEN}✓ $name passed${NC}"
  else
    echo -e "${RED}✗ $name failed (exit code: $result)${NC}"
    return $result
  fi
}

# Build if requested
if [ "$BUILD" = true ]; then
  echo -e "\n${YELLOW}Building Docker images...${NC}"
  docker compose --profile core build
  docker compose --profile testing build
fi

# Start core services
echo -e "\n${YELLOW}Starting core services...${NC}"
docker compose --profile core up -d

# Wait for services to be healthy
echo "Waiting for services to be healthy..."
sleep 10

# Track overall result
OVERALL_RESULT=0

# Run backend unit tests
if [ "$RUN_UNIT" = true ]; then
  run_test_suite "Backend Unit Tests" "backend-test" "test-backend" "php artisan test tests/Unit --parallel" || OVERALL_RESULT=1
fi

# Run backend feature tests
if [ "$RUN_FEATURE" = true ]; then
  run_test_suite "Backend Feature Tests" "backend-test" "test-backend" "php artisan test tests/Feature --parallel" || OVERALL_RESULT=1
fi

# Run BDD tests
if [ "$RUN_BDD" = true ]; then
  run_test_suite "BDD Tests" "bdd-test" "test-bdd" "php artisan test tests/BDD --parallel" || OVERALL_RESULT=1
fi

# Run ATDD tests
if [ "$RUN_ATDD" = true ]; then
  run_test_suite "ATDD Tests" "atdd-test" "test-atdd" "php artisan test tests/ATDD --parallel" || OVERALL_RESULT=1
fi

# Run frontend unit tests
if [ "$RUN_FRONTEND_UNIT" = true ]; then
  run_test_suite "Frontend Unit Tests" "frontend-test" "test-frontend" "npm run test:unit -- --run" || OVERALL_RESULT=1
fi

# Run Playwright API tests
if [ "$RUN_API" = true ]; then
  run_test_suite "Playwright API Tests" "api-test" "test-api" "npm run test:api" || OVERALL_RESULT=1
fi

# Run Playwright E2E tests
if [ "$RUN_E2E" = true ]; then
  run_test_suite "Playwright E2E Tests" "e2e-test" "test-e2e" "npm run test:e2e" || OVERALL_RESULT=1
fi

# Cleanup
if [ "$CLEANUP" = true ]; then
  echo -e "\n${YELLOW}Cleaning up...${NC}"
  docker compose --profile core down
  docker compose --profile testing down -v
fi

# Summary
echo -e "\n=========================================="
if [ $OVERALL_RESULT -eq 0 ]; then
  echo -e "${GREEN}All tests passed!${NC}"
else
  echo -e "${RED}Some tests failed!${NC}"
fi
echo "=========================================="

exit $OVERALL_RESULT