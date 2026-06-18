@echo off
chcp 65001 >nul
cd /d T:\laragon\www\vakil
powershell -ExecutionPolicy Bypass -File "T:\laragon\www\vakil\deploy.ps1" %*
pause
