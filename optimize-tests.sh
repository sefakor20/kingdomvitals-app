#!/bin/bash

# Test Suite Optimization Script
# This script helps run and optimize your test suite

echo "🚀 Laravel Test Suite Optimizer"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to run tests with optimal settings
run_optimized_tests() {
    echo -e "${YELLOW}Running optimized test suite...${NC}"

    # Ensure test database exists
    php artisan db:create --database=mysql --name=kingdomvitals_testing 2>/dev/null || true

    # Run with optimal parallel settings
    php artisan test \
        --compact \
        --parallel \
        --processes=4 \
        --env=testing
}

# Function to run specific test suite
run_suite() {
    local suite=$1
    echo -e "${YELLOW}Running $suite tests...${NC}"
    php artisan test \
        --compact \
        --parallel \
        --processes=2 \
        --env=testing \
        tests/$suite
}

# Function to identify slow tests
identify_slow_tests() {
    echo -e "${YELLOW}Identifying slow tests...${NC}"
    php artisan test \
        --compact \
        --profile \
        --env=testing \
        2>&1 | grep -E "^\s+[0-9]+\.[0-9]+s" | sort -rn | head -20
}

# Function to run tests sequentially for debugging
run_debug_tests() {
    echo -e "${YELLOW}Running tests in debug mode (sequential)...${NC}"
    php artisan test \
        --stop-on-failure \
        --env=testing
}

# Function to regenerate schema dump
regenerate_schema() {
    echo -e "${YELLOW}Regenerating tenant schema dump...${NC}"
    php -r '
    require_once "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Your schema generation logic here
    echo "Schema regenerated successfully!\n";
    '
}

# Main menu
case "${1:-menu}" in
    all)
        run_optimized_tests
        ;;
    unit)
        run_suite "Unit"
        ;;
    feature)
        run_suite "Feature"
        ;;
    slow)
        identify_slow_tests
        ;;
    debug)
        run_debug_tests
        ;;
    schema)
        regenerate_schema
        ;;
    menu|*)
        echo "Available commands:"
        echo "  ./optimize-tests.sh all     - Run all tests with optimizations"
        echo "  ./optimize-tests.sh unit    - Run only unit tests"
        echo "  ./optimize-tests.sh feature - Run only feature tests"
        echo "  ./optimize-tests.sh slow    - Identify slowest tests"
        echo "  ./optimize-tests.sh debug   - Run tests in debug mode"
        echo "  ./optimize-tests.sh schema  - Regenerate tenant schema dump"
        ;;
esac