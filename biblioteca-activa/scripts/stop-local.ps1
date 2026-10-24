$workspacePath = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$pgCtl = Join-Path $workspacePath '.tools\postgresql-18.6\pgsql\bin\pg_ctl.exe'
$pgData = Join-Path $workspacePath '.data\postgresql'

if ((Test-Path -LiteralPath $pgCtl) -and (Test-Path -LiteralPath (Join-Path $pgData 'postmaster.pid'))) {
    & $pgCtl -D $pgData stop -m fast | Out-Null
}

$ports = 8000, 8443

foreach ($port in $ports) {
    Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty OwningProcess -Unique |
        ForEach-Object { Stop-Process -Id $_ -ErrorAction SilentlyContinue }
}

Write-Host 'Servicios locales de Biblioteca Activa detenidos.' -ForegroundColor Yellow
