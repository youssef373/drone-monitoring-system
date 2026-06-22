# Pre-Deployment Checklist for Windows PowerShell
$ErrorActionPreference = "Continue"

# Change to project directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
Set-Location $projectDir

Write-Host "Running checks in: $projectDir" -ForegroundColor DarkGray
Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  Pre-Deployment Checklist" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$FAILED = 0

# 1. Check PHP syntax
Write-Host "[1/5] Checking PHP syntax..." -ForegroundColor Yellow
$phpCheck = php -l "$projectDir\artisan" 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  OK" -ForegroundColor Green
} else {
    Write-Host "  FAILED" -ForegroundColor Red
    $FAILED = 1
}

# 2. Run Pint (code style)
Write-Host "[2/5] Running Laravel Pint (code style)..." -ForegroundColor Yellow
php "$projectDir\vendor\bin\pint" --test 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  OK" -ForegroundColor Green
} else {
    Write-Host "  FAILED - Run 'vendor/bin/pint' to fix" -ForegroundColor Red
    $FAILED = 1
}

# 3. Build frontend assets and run tests
Write-Host "[3/5] Building frontend assets and running tests..." -ForegroundColor Yellow
Set-Location $projectDir
npm install 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  FAILED - npm install failed" -ForegroundColor Red
    $FAILED = 1
} else {
    npm run build 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  FAILED - Asset build failed" -ForegroundColor Red
        $FAILED = 1
    } else {
        Write-Host "  Assets built OK" -ForegroundColor Green
        composer run test 2>&1 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  Tests OK" -ForegroundColor Green
        } else {
            Write-Host "  Tests FAILED" -ForegroundColor Red
            $FAILED = 1
        }
    }
}

# 4. Check .env file exists and has APP_KEY
Write-Host "[4/5] Checking environment configuration..." -ForegroundColor Yellow
$envPath = "$projectDir\.env"
if (Test-Path $envPath) {
    $content = Get-Content $envPath -Raw
    if ($content -match "APP_KEY=base64:") {
        Write-Host "  OK" -ForegroundColor Green
    } else {
        Write-Host "  FAILED - APP_KEY missing" -ForegroundColor Red
        $FAILED = 1
    }
} else {
    Write-Host "  FAILED - .env not found" -ForegroundColor Red
    $FAILED = 1
}

# 5. Check Docker can build
Write-Host "[5/5] Testing Docker build..." -ForegroundColor Yellow
docker build -f "$projectDir\docker\app\Dockerfile" --target production -t drone-monitoring:test "$projectDir" 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  OK" -ForegroundColor Green
    docker rmi drone-monitoring:test 2>&1 | Out-Null
} else {
    Write-Host "  FAILED" -ForegroundColor Red
    $FAILED = 1
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
if ($FAILED -eq 0) {
    Write-Host "All checks passed! Ready for deployment." -ForegroundColor Green
    exit 0
} else {
    Write-Host "Pre-deployment checks failed." -ForegroundColor Red
    exit 1
}
