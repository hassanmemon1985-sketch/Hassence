@echo off
REM Hassence scheduled backup (Windows Task Scheduler)
REM Run daily: schtasks /TN HassenceBackup /TR "powershell -ExecutionPolicy Bypass -File C:\Users\User\Desktop\Hassence\scripts\backup.ps1" /SC DAILY /ST 02:00

setlocal

set REPO=C:\Users\User\Desktop\Hassence
set HASSENCE_DB_HOST=localhost
set HASSENCE_DB_NAME=hassence_wp589
set HASSENCE_DB_USER=YOUR_DB_USER
set HASSENCE_DB_PASS=YOUR_DB_PASS

REM Push via HTTPS using the cached PAT (git credential manager stores it after first push)
set HASSENCE_SSH_KEY=

cd /d "%REPO%"
python scripts\backup.py "Scheduled backup %date% %time%"
exit /b %errorlevel%