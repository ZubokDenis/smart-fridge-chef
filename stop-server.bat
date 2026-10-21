@echo off
chcp 65001 >nul
echo Остановка локального сервера WordPress...
taskkill /F /IM php.exe /T 2>nul
echo Сервер остановлен.
pause
