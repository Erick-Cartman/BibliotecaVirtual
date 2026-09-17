# Biblioteca Activa

MVP de biblioteca escolar con enfoque modular UDG. Incluye landing pública, autenticación, panel por rol, catálogo, reservaciones, préstamos, devoluciones y administración de usuarios.

## Stack

- Laravel 13 y PHP 8.5
- Livewire 4 con componentes de página única
- MongoDB Community 8.2 y driver oficial para Laravel
- Tailwind CSS 4 y Vite
- Roles: estudiante, bibliotecario y administrador

## Abrir en localhost

La versión preparada en este equipo se inicia desde PowerShell:

```powershell
cd C:\modular\biblioteca-activa
powershell -ExecutionPolicy Bypass -File .\scripts\start-local.ps1
```

Después abre [http://127.0.0.1:8000](http://127.0.0.1:8000).

Para detener los dos servicios locales:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\stop-local.ps1
```

## Cuentas de demostración

Todas usan la contraseña `Biblioteca2026!`.

| Rol | Correo |
|---|---|
| Estudiante | `estudiante@biblioteca.test` |
| Bibliotecario | `bibliotecario@biblioteca.test` |
| Administrador | `admin@biblioteca.test` |

Estas credenciales son únicamente para localhost y deben cambiarse antes de publicar.

## Preparar una instalación desde cero

```powershell
$env:PHPRC='C:\modular\.tools'
php ..\.tools\composer.phar install
npm.cmd install
npm.cmd run build
php artisan migrate:fresh --seed --force
```

La carga es idempotente y proviene de `database/seeders/data/books.json`, generado desde la hoja `COMPLETO` del inventario fuente. Para regenerarla sin modificar el Excel:

```powershell
cd C:\modular
.\.venv\Scripts\python.exe .\scripts\export_acervo_seed.py
```

## Verificación

```powershell
$env:PHPRC='C:\modular\.tools'
php artisan test --compact
php vendor/bin/pint --dirty --format agent
npm.cmd run build
```

## Publicación posterior en dominio

Antes de desplegar, configura el servidor con HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, una clave propia, `APP_URL` con el dominio y credenciales MongoDB privadas. El directorio público del dominio debe apuntar a `public/`; ejecuta migraciones con `--force`, compila Vite y aplica `php artisan optimize`. Usa un servidor web con PHP-FPM y conserva copias de seguridad de MongoDB. No publiques las cuentas demo.

## Estructura modular

- `app/Actions`: reglas de circulación reutilizables.
- `app/Models`: documentos MongoDB y relaciones.
- `app/Policies` y `app/Http/Middleware`: autorización por rol.
- `resources/views/pages`: pantallas Livewire.
- `database/seeders`: usuarios demo e importación del acervo.
- `tests/Feature`: autenticación, autorización, catálogo, seguridad y circulación.
