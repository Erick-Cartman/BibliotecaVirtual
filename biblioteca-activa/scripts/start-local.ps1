$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
$workspacePath = Split-Path -Parent $projectPath
$phpExe = 'C:\php\php.exe'
$phpIni = Join-Path $workspacePath '.tools\php.ini'
$pgBin = Join-Path $workspacePath '.tools\postgresql-18.6\pgsql\bin'
$pgCtl = Join-Path $pgBin 'pg_ctl.exe'
$pgData = Join-Path $workspacePath '.data\postgresql'
$logPath = Join-Path $workspacePath '.logs'

New-Item -ItemType Directory -Force -Path $logPath | Out-Null

if (-not (Test-Path -LiteralPath (Join-Path $pgData 'PG_VERSION'))) {
    throw "No existe el clúster PostgreSQL en $pgData. Inicialízalo antes de iniciar la aplicación."
}

$postgresListening = Test-NetConnection 127.0.0.1 -Port 5432 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $postgresListening) {
    & $pgCtl -D $pgData -l (Join-Path $logPath 'postgresql.log') -o '-p 5432 -h 127.0.0.1' start | Out-Null
}

$webListening = Test-NetConnection 127.0.0.1 -Port 8000 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $webListening) {
    Start-Process -FilePath $phpExe -ArgumentList @('-c', $phpIni, '-S', '127.0.0.1:8000', (Join-Path $projectPath 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php')) -WorkingDirectory (Join-Path $projectPath 'public') -RedirectStandardOutput (Join-Path $logPath 'laravel-server.log') -RedirectStandardError (Join-Path $logPath 'laravel-server-error.log') -WindowStyle Hidden
}

Start-Sleep -Seconds 1
Write-Host 'Biblioteca Activa disponible en http://127.0.0.1:8000' -ForegroundColor Green
