`@echo off

rem -------------------------------------------------------------
rem 命令行 for Windows. 如果不对,请自行修改php.exe到实际php文件
rem -------------------------------------------------------------

@SETLOCAL

set PWD_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%PWD_PATH%redis-cli" %*

@endlocal
`