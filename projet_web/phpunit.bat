@echo off
REM Wrapper pour PHPUnit qui utilise le bon php.ini de MAMP
REM Utilisation : phpunit.bat [arguments]

set PHP_EXE=C:\MAMP\bin\php\php8.3.1\php.exe
set PHP_INI=C:\MAMP\conf\php8.3.1\php.ini
set PHPUNIT_PHAR=%~dp0tools\phpunit.phar

if not exist "%PHP_EXE%" (
    echo ERREUR : PHP non trouve a %PHP_EXE%
    exit /b 1
)

if not exist "%PHPUNIT_PHAR%" (
    echo ERREUR : PHPUnit non trouve. Executez install_phpunit.ps1
    exit /b 1
)

"%PHP_EXE%" -c "%PHP_INI%" "%PHPUNIT_PHAR%" %*
