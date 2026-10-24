# Iniciar manualmente Biblioteca Activa

La aplicación utiliza PostgreSQL y el servidor Laravel. MongoDB se conserva únicamente para respaldos o una eventual restauración.

## 1. Iniciar PostgreSQL

Abre una ventana de PowerShell y ejecuta:

```powershell
cd C:\modular\biblioteca-activa
$pgBin = 'C:\modular\.tools\postgresql-18.6\pgsql\bin'
$pgData = 'C:\modular\.data\postgresql'
& "$pgBin\pg_ctl.exe" -D $pgData -l 'C:\modular\.logs\postgresql.log' `
  -o '-p 5432 -h 127.0.0.1' start
```

PostgreSQL quedará escuchando en `127.0.0.1:5432`.

## 2. Iniciar Laravel

Abre una segunda ventana de PowerShell y ejecuta:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa\public

& C:\php\php.exe `
  -c C:\modular\BibliotecaActiva\.tools\php.ini `
  -S 127.0.0.1:8000 `
  "C:\modular\BibliotecaActiva\biblioteca-activa\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
```

Después abre [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Acceso desde la red local

Si otros equipos de la misma red necesitan acceder a la aplicación, inicia Laravel escuchando en todas las interfaces:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa\public

& C:\php\php.exe `
  -c C:\modular\BibliotecaActiva\.tools\php.ini `
  -S 0.0.0.0:8000 `
  "C:\modular\BibliotecaActiva\biblioteca-activa\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
```

Consulta la IP del equipo con `ipconfig` y abre desde el otro equipo:

```text
http://IP_DEL_EQUIPO:8000
```

PostgreSQL permanece escuchando únicamente en `127.0.0.1:5432`; Laravel se conecta a él localmente.

## Cámara desde otro dispositivo con HTTPS

Para que el navegador permita usar la cámara desde otro equipo, inicia la variante HTTPS:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa
powershell -ExecutionPolicy Bypass -File .\scripts\start-network-https.ps1
```

Abre la dirección HTTPS que muestre el script, normalmente `https://IP_DEL_EQUIPO:8443`. El certificado local se guarda en `C:\modular\BibliotecaActiva\.tools\https`; si el dispositivo muestra una advertencia, instala el archivo `.cer` como certificado de confianza.

## Verificar los servicios

```powershell
Test-NetConnection 127.0.0.1 -Port 5432
Test-NetConnection 127.0.0.1 -Port 8000
```

El resultado debe mostrar `TcpTestSucceeded : True` en ambos casos.

## Detener los servicios

Para detenerlos con el script del proyecto, incluidos los servicios HTTPS de red:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa
powershell -ExecutionPolicy Bypass -File .\scripts\stop-local.ps1
```

También puedes presionar `Ctrl+C` en cada ventana donde se esté ejecutando un servicio.

## Inicio automático de ambos servicios

Como alternativa, puedes iniciar PostgreSQL y Laravel juntos con:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa
powershell -ExecutionPolicy Bypass -File .\scripts\start-local.ps1
```
