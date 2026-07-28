@echo off
setlocal EnableExtensions
set PHP_ROOT=%~dp0..\tools\php82-portable
set PATH=%PHP_ROOT%;%PATH%
set XDEBUG_MODE=off
cd /d "%~dp0"

if not exist "%PHP_ROOT%\php.exe" (
  echo [user-php] ERROR: php not found: %PHP_ROOT%\php.exe
  exit /b 1
)

where composer >nul 2>&1
if errorlevel 1 (
  echo [user-php] composer not in PATH, trying php composer.phar ...
  if exist composer.phar (
    "%PHP_ROOT%\php.exe" composer.phar install --no-dev --optimize-autoloader --no-interaction
  ) else (
    echo [user-php] ERROR: composer not found
    exit /b 1
  )
) else (
  composer install --no-dev --optimize-autoloader --no-interaction
)

echo [user-php] build done.
endlocal