@echo off
rem Envio semanal de reportes de EduNexo por WhatsApp.
rem Lo ejecuta el Programador de tareas de Windows (ver README). Tambien se puede lanzar a mano:
rem   cron\enviar_semanal.bat                      envia
rem   cron\enviar_semanal.bat --solo-encolar       solo encola y cuenta, sin enviar
rem   cron\enviar_semanal.bat --semana=2026-09-28  fuerza la semana
rem Requiere que Laragon (MySQL) y Evolution API (start-evolution.bat) esten encendidos.
setlocal

set "PROYECTO=%~dp0.."
set "PHP="
for /d %%d in (C:\laragon\bin\php\php-*) do if exist "%%d\php.exe" set "PHP=%%d\php.exe"

if not defined PHP (
    echo No se encontro php.exe en C:\laragon\bin\php
    exit /b 2
)

if not exist "%PROYECTO%\logs" mkdir "%PROYECTO%\logs"

echo ===== %date% %time% ===== >> "%PROYECTO%\logs\envios_semanales.log"
"%PHP%" "%PROYECTO%\cron\procesar_envios.php" %* >> "%PROYECTO%\logs\envios_semanales.log" 2>&1
exit /b %errorlevel%
