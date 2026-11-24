@echo off
REM Test runner script for auction marketplace (Windows)
REM Runs all test suites in Docker containers

setlocal enabledelayedexpansion

set SCRIPT_DIR=%~dp0
set PROJECT_ROOT=%SCRIPT_DIR%

echo ==========================================
echo Auction Marketplace Test Runner (Windows)
echo ==========================================

REM Default options
set RUN_UNIT=true
set RUN_FEATURE=true
set RUN_BDD=true
set RUN_ATDD=true
set RUN_FRONTEND_UNIT=true
set RUN_E2E=true
set RUN_API=true
set BUILD=false
set CLEANUP=true

REM Parse arguments
:parse_args
if "%~1"=="" goto :run_tests
if "%~1"=="--unit" (
    set RUN_UNIT=true
    set RUN_FEATURE=false
    set RUN_BDD=false
    set RUN_ATDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_E2E=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--feature" (
    set RUN_FEATURE=true
    set RUN_UNIT=false
    set RUN_BDD=false
    set RUN_ATDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_E2E=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--bdd" (
    set RUN_BDD=true
    set RUN_UNIT=false
    set RUN_FEATURE=false
    set RUN_ATDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_E2E=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--atdd" (
    set RUN_ATDD=true
    set RUN_UNIT=false
    set RUN_FEATURE=false
    set RUN_BDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_E2E=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--frontend" (
    set RUN_FRONTEND_UNIT=true
    set RUN_UNIT=false
    set RUN_FEATURE=false
    set RUN_BDD=false
    set RUN_ATDD=false
    set RUN_E2E=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--e2e" (
    set RUN_E2E=true
    set RUN_UNIT=false
    set RUN_FEATURE=false
    set RUN_BDD=false
    set RUN_ATDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_API=false
    shift
    goto :parse_args
)
if "%~1"=="--api" (
    set RUN_API=true
    set RUN_UNIT=false
    set RUN_FEATURE=false
    set RUN_BDD=false
    set RUN_ATDD=false
    set RUN_FRONTEND_UNIT=false
    set RUN_E2E=false
    shift
    goto :parse_args
)
if "%~1"=="--all" (
    set RUN_UNIT=true
    set RUN_FEATURE=true
    set RUN_BDD=true
    set RUN_ATDD=true
    set RUN_FRONTEND_UNIT=true
    set RUN_E2E=true
    set RUN_API=true
    shift
    goto :parse_args
)
if "%~1"=="--build" (
    set BUILD=true
    shift
    goto :parse_args
)
if "%~1"=="--no-cleanup" (
    set CLEANUP=false
    shift
    goto :parse_args
)
if "%~1"=="--help" (
    echo Usage: %~nx0 [options]
    echo Options:
    echo   --unit        Run backend unit tests only
    echo   --feature     Run backend feature tests only
    echo   --bdd         Run BDD tests only
    echo   --atdd        Run ATDD tests only
    echo   --frontend    Run frontend unit tests only
    echo   --e2e         Run Playwright E2E tests only
    echo   --api         Run Playwright API tests only
    echo   --all         Run all test suites (default)
    echo   --build       Build Docker images before running tests
    echo   --no-cleanup  Don't stop containers after tests
    echo   --help        Show this help message
    exit /b 0
)
echo Unknown option: %~1
exit /b 1

:run_tests

REM Build if requested
if "%BUILD%"=="true" (
    echo.
    echo Building Docker images...
    docker compose --profile core build
    docker compose --profile testing build
)

REM Start core services
echo.
echo Starting core services...
docker compose --profile core up -d

REM Wait for services to be healthy
echo Waiting for services to be healthy...
timeout /t 10 >nul

REM Track overall result
set OVERALL_RESULT=0

REM Function to run a test suite
REM Usage: call :run_test_suite "Name" "profile" "service" "command"
:run_test_suite
set name=%~1
set profile=%~2
set service=%~3
set command=%~4

echo.
echo Running %name%...
echo Profile: %profile%, Service: %service%

if "%BUILD%"=="true" (
    docker compose --profile %profile% build %service%
)

docker compose --profile %profile% run --rm %service% %command%
if errorlevel 1 (
    echo [FAILED] %name% failed
    set OVERALL_RESULT=1
) else (
    echo [PASSED] %name% passed
)
goto :eof

REM Run backend unit tests
if "%RUN_UNIT%"=="true" (
    call :run_test_suite "Backend Unit Tests" "backend-test" "test-backend" "php artisan test tests/Unit --parallel"
)

REM Run backend feature tests
if "%RUN_FEATURE%"=="true" (
    call :run_test_suite "Backend Feature Tests" "backend-test" "test-backend" "php artisan test tests/Feature --parallel"
)

REM Run BDD tests
if "%RUN_BDD%"=="true" (
    call :run_test_suite "BDD Tests" "bdd-test" "test-bdd" "php artisan test tests/BDD --parallel"
)

REM Run ATDD tests
if "%RUN_ATDD%"=="true" (
    call :run_test_suite "ATDD Tests" "atdd-test" "test-atdd" "php artisan test tests/ATDD --parallel"
)

REM Run frontend unit tests
if "%RUN_FRONTEND_UNIT%"=="true" (
    call :run_test_suite "Frontend Unit Tests" "frontend-test" "test-frontend" "npm run test:unit -- --run"
)

REM Run Playwright API tests
if "%RUN_API%"=="true" (
    call :run_test_suite "Playwright API Tests" "api-test" "test-api" "npm run test:api"
)

REM Run Playwright E2E tests
if "%RUN_E2E%"=="true" (
    call :run_test_suite "Playwright E2E Tests" "e2e-test" "test-e2e" "npm run test:e2e"
)

REM Cleanup
if "%CLEANUP%"=="true" (
    echo.
    echo Cleaning up...
    docker compose --profile core down
    docker compose --profile testing down -v
)

REM Summary
echo.
echo ==========================================
if %OVERALL_RESULT% equ 0 (
    echo All tests passed!
) else (
    echo Some tests failed!
)
echo ==========================================

exit /b %OVERALL_RESULT%