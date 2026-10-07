$ports = 8000, 8443, 27017

foreach ($port in $ports) {
    Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -ExpandProperty OwningProcess -Unique |
        ForEach-Object { Stop-Process -Id $_ -ErrorAction SilentlyContinue }
}

Write-Host 'Servicios locales de Biblioteca Activa detenidos.' -ForegroundColor Yellow
