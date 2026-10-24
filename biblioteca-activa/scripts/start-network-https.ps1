$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
$workspacePath = Split-Path -Parent $projectPath
$phpExe = 'C:\php\php.exe'
$phpIni = Join-Path $workspacePath '.tools\php.ini'
$pgBin = Join-Path $workspacePath '.tools\postgresql-18.6\pgsql\bin'
$pgCtl = Join-Path $pgBin 'pg_ctl.exe'
$pgData = Join-Path $workspacePath '.data\postgresql'
$logPath = Join-Path $workspacePath '.logs'
$httpsPath = Join-Path $workspacePath '.tools\https'
$httpsPort = 8443

New-Item -ItemType Directory -Force -Path $logPath, $httpsPath | Out-Null

$networkIp = Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.InterfaceAlias -eq 'Wi-Fi' -and $_.IPAddress -notlike '169.254.*' } |
    Select-Object -First 1 -ExpandProperty IPAddress

if (-not $networkIp) {
    throw 'No se encontró una dirección IPv4 activa en la interfaz Wi-Fi.'
}

$ipFilePart = $networkIp -replace '\.', '-'
$pfxPath = Join-Path $httpsPath "biblioteca-activa-lan-$ipFilePart.pfx"
$certificatePath = Join-Path $httpsPath "biblioteca-activa-lan-$ipFilePart.cer"

$certificate = $null
if (Test-Path -LiteralPath $certificatePath) {
    $publicCertificate = [System.Security.Cryptography.X509Certificates.X509Certificate2]::new($certificatePath)
    $certificate = Get-Item -LiteralPath "Cert:\CurrentUser\My\$($publicCertificate.Thumbprint)" -ErrorAction SilentlyContinue
    $publicCertificate.Dispose()
}

if (-not $certificate -or -not $certificate.HasPrivateKey -or $certificate.NotAfter -le (Get-Date)) {
    $certificate = New-SelfSignedCertificate `
        -Subject "CN=$networkIp" `
        -TextExtension @("2.5.29.17={text}DNS=localhost&IPAddress=$networkIp") `
        -KeyAlgorithm RSA `
        -KeyLength 2048 `
        -HashAlgorithm SHA256 `
        -NotAfter (Get-Date).AddYears(2) `
        -CertStoreLocation 'Cert:\CurrentUser\My'
    Export-Certificate -Cert $certificate -FilePath $certificatePath | Out-Null
}

$passwordBytes = New-Object byte[] 32
$randomGenerator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try {
    $randomGenerator.GetBytes($passwordBytes)
} finally {
    $randomGenerator.Dispose()
}
$certificatePassword = [Convert]::ToBase64String($passwordBytes)
$securePassword = ConvertTo-SecureString -String $certificatePassword -AsPlainText -Force
Export-PfxCertificate -Cert $certificate -FilePath $pfxPath -Password $securePassword | Out-Null

if (-not (Test-Path -LiteralPath (Join-Path $pgData 'PG_VERSION'))) {
    throw "No existe el clúster PostgreSQL en $pgData. Inicialízalo antes de iniciar la aplicación."
}

$postgresListening = Test-NetConnection 127.0.0.1 -Port 5432 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $postgresListening) {
    & $pgCtl -D $pgData -l (Join-Path $logPath 'postgresql.log') -o '-p 5432 -h 127.0.0.1' start | Out-Null
}

$webProcess = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue |
    Select-Object -ExpandProperty OwningProcess -Unique
if ($webProcess) {
    Stop-Process -Id $webProcess -Force -ErrorAction SilentlyContinue
}

$httpsProcess = Get-NetTCPConnection -LocalPort $httpsPort -State Listen -ErrorAction SilentlyContinue |
    Select-Object -ExpandProperty OwningProcess -Unique
if ($httpsProcess) {
    Stop-Process -Id $httpsProcess -Force -ErrorAction SilentlyContinue
}

$env:APP_URL = "https://${networkIp}:${httpsPort}"
Start-Process -FilePath $phpExe `
    -ArgumentList @('-c', $phpIni, '-S', '127.0.0.1:8000', (Join-Path $projectPath 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php')) `
    -WorkingDirectory (Join-Path $projectPath 'public') `
    -RedirectStandardOutput (Join-Path $logPath 'laravel-server.log') `
    -RedirectStandardError (Join-Path $logPath 'laravel-server-error.log') `
    -WindowStyle Hidden

$env:BA_HTTPS_PFX = $pfxPath
$env:BA_HTTPS_PASSWORD = $certificatePassword
$env:BA_HTTPS_PORT = [string] $httpsPort
$env:BA_HTTP_PORT = '8000'
$nodeExe = (Get-Command node.exe).Source
Start-Process -FilePath $nodeExe `
    -ArgumentList (Join-Path $PSScriptRoot 'https-proxy.mjs') `
    -RedirectStandardOutput (Join-Path $logPath 'https-proxy.log') `
    -RedirectStandardError (Join-Path $logPath 'https-proxy-error.log') `
    -WindowStyle Hidden

Start-Sleep -Seconds 2
Write-Host "Biblioteca Activa disponible en https://${networkIp}:${httpsPort}" -ForegroundColor Green
Write-Host "Certificado para instalar en otro dispositivo: $certificatePath" -ForegroundColor Yellow
