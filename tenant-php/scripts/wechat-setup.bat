@echo off
cd /d D:\chenguang\MineAdmin\user-php
set PHP=D:\chenguang\MineAdmin\tools\php82-portable\php.exe

echo === unit test ===
if exist vendor\bin\phpunit.bat (
  call vendor\bin\phpunit.bat tests\Unit\Wechat\WechatCallbackServiceTest.php
) else if exist vendor\phpunit\phpunit\phpunit (
  %PHP% vendor\phpunit\phpunit\phpunit tests\Unit\Wechat\WechatCallbackServiceTest.php
) else (
  echo phpunit not found, skip
)

echo === migrate ===
%PHP% bin\hyperf.php migrate
if errorlevel 1 exit /b 1

echo === seed wechat menu ===
%PHP% bin\hyperf.php db:seed --path=databases/seeders/wechat_menu_20260716.php
if errorlevel 1 (
  echo seed with --path failed, try class path
  %PHP% bin\hyperf.php db:seed --class=WechatMenu20260716
)
