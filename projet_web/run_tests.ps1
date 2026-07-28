# Script PowerShell pour exécuter les tests PHPUnit
# Utilise le PHAR PHPUnit téléchargé localement

$phpunitPath = Join-Path $PSScriptRoot "tools\phpunit.phar"

if (-not (Test-Path $phpunitPath)) {
    Write-Host "ERREUR : PHPUnit non trouvé. Exécutez d'abord install_phpunit.ps1" -ForegroundColor Red
    exit 1
}

# Variable pour stocker le chemin complet de PHP
$phpExePath = $null

# Vérifier si PHP est disponible dans le PATH
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpPath) {
    $phpExePath = $phpPath.Source
} else {
    # Essayer avec MAMP - chercher dans plusieurs chemins possibles
    $mampPaths = @(
        "C:\MAMP\bin\php",
        "C:\Program Files\MAMP\bin\php",
        "C:\Program Files (x86)\MAMP\bin\php"
    )
    
    foreach ($mampPath in $mampPaths) {
        if (Test-Path $mampPath) {
            # Chercher la version PHP la plus récente (8.x ou 7.x)
            $phpVersions = Get-ChildItem $mampPath -Directory -ErrorAction SilentlyContinue | 
                          Where-Object { $_.Name -match '^php[78]\.' } |
                          Sort-Object Name -Descending
            
            if ($phpVersions) {
                # Préférer PHP 8.x si disponible
                $preferredVersion = $phpVersions | Where-Object { $_.Name -match '^php8\.' } | Select-Object -First 1
                if (-not $preferredVersion) {
                    $preferredVersion = $phpVersions | Select-Object -First 1
                }
                
                $phpExe = Join-Path $preferredVersion.FullName "php.exe"
                if (Test-Path $phpExe) {
                    $phpExePath = $phpExe
                    break
                }
            }
        }
    }
    
    if (-not $phpExePath) {
        Write-Host "ERREUR : PHP non trouvé. Vérifiez que MAMP est installé." -ForegroundColor Red
        exit 1
    }
}

# Spécifier le php.ini de MAMP
$phpIniPath = "C:\MAMP\conf\php8.3.1\php.ini"
if (Test-Path $phpIniPath) {
    $phpArgs = @("-c", $phpIniPath, $phpunitPath) + $args
} else {
    $phpArgs = @($phpunitPath) + $args
}

Write-Host "Exécution des tests PHPUnit..." -ForegroundColor Cyan
& $phpExePath $phpArgs
