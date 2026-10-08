# Windows version of db-backup.sh, for Task Scheduler or a double-click run.
# Exports the database to backups\ (git-ignored). Credentials come from wp-config.php via WP-CLI.
#
# Usage:   powershell -ExecutionPolicy Bypass -File scripts\db-backup.ps1 [-Keep 14]
# Daily:   schtasks /Create /TN "Labora WP DB backup" /SC DAILY /ST 18:00 `
#            /TR "powershell -ExecutionPolicy Bypass -File D:\xampp\htdocs\labora-wp\scripts\db-backup.ps1"
param([int]$Keep = 14)
$ErrorActionPreference = 'Stop'

$Root  = Split-Path -Parent $PSScriptRoot
$Php   = if ($env:PHP_BIN) { $env:PHP_BIN } else { 'D:\xampp\php\php.exe' }
$WpCli = if ($env:WP_CLI_PHAR) { $env:WP_CLI_PHAR } else { 'D:\xampp\tools\wp-cli.phar' }
$OutDir = Join-Path $Root 'backups'
New-Item -ItemType Directory -Force $OutDir | Out-Null

$Stamp = Get-Date -Format 'yyyy-MM-dd-HHmm'
$Sql   = Join-Path $OutDir "labora-wp-$Stamp.sql"

Push-Location $Root
try {
  & $Php $WpCli db export $Sql --add-drop-table --quiet
  if ($LASTEXITCODE -ne 0) { throw "wp db export failed ($LASTEXITCODE)" }
} finally { Pop-Location }

# Compress (.zip, built into Windows) and remove the plain .sql
$Zip = "$Sql.zip"
Compress-Archive -Path $Sql -DestinationPath $Zip -Force
Remove-Item $Sql
Write-Output "Saved $Zip"

# Keep the newest $Keep backups
Get-ChildItem $OutDir -Filter 'labora-wp-*.sql*' | Sort-Object LastWriteTime -Descending |
  Select-Object -Skip $Keep | Remove-Item -Force
