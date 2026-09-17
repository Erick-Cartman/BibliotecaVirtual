$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
$workspacePath = Split-Path -Parent $projectPath
$phpExe = 'C:\php\php.exe'
$phpIni = Join-Path $workspacePath '.tools\php.ini'
$mongoExe = Join-Path $workspacePath '.tools\mongodb\mongodb-win32-x86_64-windows-8.2.12\bin\mongod.exe'
$mongoData = Join-Path $workspacePath '.data\mongodb'
$logPath = Join-Path $workspacePath '.logs'

New-Item -ItemType Directory -Force -Path $mongoData, $logPath | Out-Null

$mongoListening = Test-NetConnection 127.0.0.1 -Port 27017 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $mongoListening) {
    Start-Process -FilePath $mongoExe -ArgumentList @('--dbpath', $mongoData, '--bind_ip', '127.0.0.1', '--port', '27017', '--logpath', (Join-Path $logPath 'mongod.log'), '--logappend') -WindowStyle Hidden
}

$webListening = Test-NetConnection 127.0.0.1 -Port 8000 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $webListening) {
    Start-Process -FilePath $phpExe -ArgumentList @('-c', $phpIni, '-S', '127.0.0.1:8000', (Join-Path $projectPath 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php')) -WorkingDirectory (Join-Path $projectPath 'public') -RedirectStandardOutput (Join-Path $logPath 'laravel-server.log') -RedirectStandardError (Join-Path $logPath 'laravel-server-error.log') -WindowStyle Hidden
}

Start-Sleep -Seconds 1
Write-Host 'Biblioteca Activa disponible en http://127.0.0.1:8000' -ForegroundColor Green
