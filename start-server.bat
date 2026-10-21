@echo off
chcp 65001 >nul
echo ========================================================
echo   Запуск WordPress проекта "Умный Шеф"
echo ========================================================
echo.

cd /d "%~dp0"

if not exist "tools\php\php.exe" (
    echo [ОШИБКА] PHP не найден в tools\php\php.exe
    pause
    exit /b 1
)

echo [OK] PHP 8.3 обнаружен
echo [OK] Запуск встроенного веб-сервера на http://localhost:8000...
echo.
echo Для остановки сервера закройте это окно или нажмите Ctrl+C.
echo.

start "" "http://localhost:8000"

tools\php\php.exe -S 127.0.0.1:8000 -t wordpress wordpress/router.php

pause
