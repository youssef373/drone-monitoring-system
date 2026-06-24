#!/bin/bash
set -e

echo "=========================================="
echo "  Pre-Deployment Checklist"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FAILED=0

# 1. Check PHP syntax
echo -e "${YELLOW}[1/5] Checking PHP syntax...${NC}"
if php -l artisan > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PHP syntax OK${NC}"
else
    echo -e "${RED}✗ PHP syntax errors found${NC}"
    FAILED=1
fi

# 2. Run Pint (code style)
echo -e "${YELLOW}[2/5] Running Laravel Pint (code style)...${NC}"
if vendor/bin/pint --test > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Code style OK${NC}"
else
    echo -e "${RED}✗ Code style issues found${NC}"
    echo "Run 'vendor/bin/pint' to fix automatically"
    FAILED=1
fi

# 3. Build frontend assets and run tests
echo -e "${YELLOW}[3/5] Building frontend assets and running tests...${NC}"
if ! npm install > /tmp/npm-output.txt 2>&1; then
    echo -e "${RED}✗ npm install failed${NC}"
    tail -20 /tmp/npm-output.txt
    FAILED=1
else
    if ! npm run build > /tmp/build-output.txt 2>&1; then
        echo -e "${RED}✗ Asset build failed${NC}"
        tail -20 /tmp/build-output.txt
        FAILED=1
    else
        echo -e "${GREEN}✓ Assets built${NC}"
        if composer run test > /tmp/test-output.txt 2>&1; then
            echo -e "${GREEN}✓ All tests passed${NC}"
        else
            echo -e "${RED}✗ Tests failed${NC}"
            tail -20 /tmp/test-output.txt
            FAILED=1
        fi
    fi
fi

# 4. Check .env file exists and has APP_KEY
echo -e "${YELLOW}[4/5] Checking environment configuration...${NC}"
if [ -f ".env" ]; then
    if grep -q "^APP_KEY=base64:" .env; then
        echo -e "${GREEN}✓ APP_KEY configured${NC}"
    else
        echo -e "${RED}✗ APP_KEY missing or invalid in .env${NC}"
        FAILED=1
    fi
else
    echo -e "${RED}✗ .env file not found${NC}"
    FAILED=1
fi

# 5. Check Docker can build
echo -e "${YELLOW}[5/5] Testing Docker build...${NC}"
if docker build -f docker/app/Dockerfile --target production -t drone-monitoring:test . > /tmp/build-output.txt 2>&1; then
    echo -e "${GREEN}✓ Docker build successful${NC}"
    docker rmi drone-monitoring:test > /dev/null 2>&1
else
    echo -e "${RED}✗ Docker build failed${NC}"
    tail -20 /tmp/build-output.txt
    FAILED=1
fi

echo ""
echo "=========================================="
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All checks passed! Ready for deployment.${NC}"
    echo ""
    echo "Next steps:"
    echo "  docker compose -f docker-compose.prod.yml up --build -d"
    exit 0
else
    echo -e "${RED}Pre-deployment checks failed.${NC}"
    echo "Fix the issues above before deploying."
    exit 1
fi
