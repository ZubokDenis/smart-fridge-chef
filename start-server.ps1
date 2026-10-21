Write-Host "========================================================" -ForegroundColor Green
Write-Host "  Запуск WordPress проекта 'Умный Шеф'" -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green
Write-Host ""

$root = $PSScriptRoot
$php = Join-Path $root "tools\php\php.exe"
$router = Join-Path $root "wordpress\router.php"
$wpDir = Join-Path $root "wordpress"

if (-not (Test-Path $php)) {
    Write-Error "PHP не найден в tools\php\php.exe"
    exit 1
}

Write-Host "[OK] Запуск веб-сервера на http://localhost:8000..." -ForegroundColor Cyan
Start-Process "http://localhost:8000"

& $php -S 127.0.0.1:8000 -t $wpDir $router
