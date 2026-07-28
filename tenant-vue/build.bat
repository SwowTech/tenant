@echo off
setlocal
cd /d "%~dp0"
if not exist "node_modules\" (
  echo [user-vue] installing dependencies ...
  call npm install
)
echo [user-vue] npm run build ...
call npm run build
if errorlevel 1 (
  echo [user-vue] build failed.
  exit /b 1
)
echo [user-vue] build done. Output in dist\
endlocal