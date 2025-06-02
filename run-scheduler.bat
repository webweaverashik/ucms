@echo off
cd /d D:\OneDrive\laragon\www\ucms
:loop
php artisan schedule:run >> scheduler.log
timeout /t 60 > nul
goto loop
