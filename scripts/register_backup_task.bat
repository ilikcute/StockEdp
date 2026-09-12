@echo off
setlocal
echo Mendaftarkan Task Scheduler Backup StockEdp...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0register_backup_task.ps1"
pause
