@echo off
setlocal
:: StockEdp - Automated Database Backup Batch Runner
:: Menjalankan backup_db.ps1 dengan hak eksekusi bypass
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0backup_db.ps1" %*
exit /b %ERRORLEVEL%
