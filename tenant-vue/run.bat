@echo off
setlocal
cd /d "%~dp0"
if not exist "node_modules\" (
  echo [user-vue] installing dependencies ...
  call npm install
)
echo [user-vue] npm run dev ...
call npm run dev
endlocal