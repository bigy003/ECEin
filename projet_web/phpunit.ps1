# Wrapper PowerShell pour PHPUnit qui utilise le bon php.ini de MAMP
# Utilisation : .\phpunit.ps1 [arguments]

$phpExe = "C:\MAMP\bin\php\php8.3.1\php.exe"
$phpIni = "C:\MAMP\conf\php8.3.1\php.ini"
$phpunitPhar = Join-Path $PSScriptRoot "tools\phpunit.phar"

if (-not (Test-Path $phpExe)) {
    Write-Host "ERREUR : PHP non trouve a $phpExe" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $phpunitPhar)) {
    Write-Host "ERREUR : PHPUnit non trouve. Executez install_phpunit.ps1" -ForegroundColor Red
    exit 1
}

& $phpExe -c $phpIni $phpunitPhar $args
