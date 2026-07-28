@echo off
chcp 65001 >nul 2>&1
setlocal EnableExtensions
set PHP_ROOT=%~dp0..\tools\php82-portable
set PATH=%PHP_ROOT%;%PATH%
set XDEBUG_MODE=off
cd /d "%~dp0"

if not exist "%PHP_ROOT%\php.exe" (
  echo [user-php] ERROR: php not found: %PHP_ROOT%\php.exe
  goto :fail
)

"%PHP_ROOT%\php.exe" -m 2>nul | findstr /I /C:"Swow" >nul
if errorlevel 1 (
  echo [user-php] ERROR: Swow extension not loaded. Check php.ini in php82-portable.
  "%PHP_ROOT%\php.exe" -v
  goto :fail
)

echo [user-php] freeing port 9501 if occupied ...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":9501 " ^| findstr "LISTENING"') do (
  echo [user-php] port 9501 in use by PID %%p, killing ...
  taskkill /PID %%p /F >nul 2>&1
)
timeout /t 1 /nobreak >nul

echo [user-php] starting foreground, Ctrl+C to stop ...
echo [user-php] php=%PHP_ROOT%\php.exe
"%PHP_ROOT%\php.exe" bin\hyperf.php start
set ERR=%ERRORLEVEL%
echo.
echo [user-php] hyperf exited with code %ERR%
if not "%ERR%"=="0" goto :fail
goto :eof

:fail
echo.
echo [user-php] start failed. Window will stay open so you can read the error.
pause
exit /b 1