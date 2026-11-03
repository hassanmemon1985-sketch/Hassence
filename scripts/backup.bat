@echo off
REM Hassence scheduled backup (Windows Task Scheduler)
REM Register once:
REM   schtasks /TN HassenceBackup /TR "C:\Users\User\Desktop\Hassence\scripts\backup.bat" /SC DAILY /ST 02:00 /RL HIGHEST
REM
REM Fill in real DB credentials in backups\.env before first run.

setlocal

set REPO=C:\Users\User\Desktop\Hassence

REM Load backups\.env into the environment for this process
if exist "%REPO%\backups\.env" (
    for /f "tokens=1,* delims==" %%a in (
        'type "%REPO%\backups\.env" ^| findstr /b /v "^#" ^| findstr "="'
    ) do set "%%a=%%b"
)

cd /d "%REPO%"
python scripts\backup.py "Scheduled backup %date% %time%"
exit /b %errorlevel%