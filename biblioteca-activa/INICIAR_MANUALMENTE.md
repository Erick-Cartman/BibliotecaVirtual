# Iniciar manualmente Biblioteca Activa

La aplicación utiliza dos servicios locales: MongoDB y el servidor Laravel.

## 1. Iniciar MongoDB

Abre una ventana de PowerShell y ejecuta:

```powershell
cd C:\modular\BibliotecaActiva

& ".\.tools\mongodb\mongodb-win32-x86_64-windows-8.2.12\bin\mongod.exe" `
  --dbpath ".\.data\mongodb" `
  --bind_ip 127.0.0.1 `
  --port 27017 `
  --logpath ".\.logs\mongod.log" `
  --logappend
```

MongoDB quedará escuchando en `127.0.0.1:27017`.

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

En este equipo, la dirección actual es `http://172.15.1.213:8000`. MongoDB permanece escuchando únicamente en `127.0.0.1:27017`; Laravel se conecta a él localmente.

## Verificar los servicios

```powershell
Test-NetConnection 127.0.0.1 -Port 27017
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

Como alternativa, puedes iniciar MongoDB y Laravel juntos con:

```powershell
cd C:\modular\BibliotecaActiva\biblioteca-activa
powershell -ExecutionPolicy Bypass -File .\scripts\start-local.ps1
```
