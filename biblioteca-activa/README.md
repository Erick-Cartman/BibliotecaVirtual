# Biblioteca Activa

MVP de biblioteca escolar con enfoque modular UDG. Incluye landing pública, autenticación, panel por rol, catálogo, reservaciones, préstamos, devoluciones y administración de usuarios.

## Stack

- Laravel 13 y PHP 8.5
- Livewire 4 con componentes de página única
- PostgreSQL 18.6 con relaciones y campos JSONB
- Tailwind CSS 4 y Vite
- Roles: estudiante, bibliotecario y administrador

## Configuración para Supabase

El proyecto ya está preparado para usar Supabase como PostgreSQL remoto. La estrategia aplicada conserva la autenticación de Laravel y las políticas de rol existentes; Supabase aporta la base de datos administrada. Así se evita duplicar usuarios mientras se valida el entorno remoto.

1. Crea un proyecto Supabase en la región más cercana a los usuarios y conserva su referencia y contraseña fuera del repositorio.
2. En Supabase, copia la cadena **Session Pooler** desde Connect. Usa el puerto `5432`; no uses el Transaction Pooler `6543` con este Laravel porque el ORM puede utilizar sentencias preparadas.
3. Copia las variables `SUPABASE_*` de `.env.example` a `.env`. Puedes usar `SUPABASE_DB_URL` o los campos individuales del pooler; si usas el pooler, el usuario tiene la forma `postgres.[PROJECT-REF]`.
4. Cambia `DB_CONNECTION=supabase`, conserva `SUPABASE_DB_SSLMODE=require` y deja `SUPABASE_DB_SCHEMA=biblioteca`.
5. Ejecuta `php artisan supabase:prepare --connection=supabase` para crear el esquema privado `biblioteca` antes de migrar.
6. Ejecuta `php artisan migrate --force` para crear las tablas y relaciones.
7. Carga los datos existentes con `php artisan database:copy-postgres-to-supabase --source-connection=pgsql --target-connection=supabase`.
8. Ejecuta `php artisan supabase:check --connection=supabase` y valida los conteos antes de poner el sistema en producción.

La migración de MongoDB a PostgreSQL ya está implementada en `database:migrate-mongodb-to-postgres`; para un proyecto remoto se debe conservar el respaldo local y validar primero en Supabase. MongoDB quedó como dependencia de desarrollo, por lo que la instalación de producción no requiere la extensión `mongodb` de PHP.

### Avatares en Supabase Storage

El proyecto incluye el disco `supabase` y el adaptador S3. En Storage crea un bucket `avatars`, habilita el protocolo S3 y configura `SUPABASE_STORAGE_ACCESS_KEY`, `SUPABASE_STORAGE_SECRET_KEY`, `SUPABASE_STORAGE_REGION`, `SUPABASE_STORAGE_ENDPOINT` y `SUPABASE_STORAGE_PUBLIC_URL`. Después cambia `AVATAR_DISK=supabase`. Las claves S3 son solo para el servidor y nunca deben exponerse en el navegador.

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

Antes de desplegar, configura el servidor con HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, una clave propia, `APP_URL` con el dominio, `AVATAR_DISK=supabase` y credenciales privadas de Supabase. El directorio público del dominio debe apuntar a `public/`; ejecuta `composer install --no-dev --optimize-autoloader`, migraciones con `--force`, compila Vite y aplica `php artisan optimize`. Conserva los respaldos de MongoDB y PostgreSQL hasta validar el entorno productivo. No publiques las cuentas demo.

## Estructura modular

- `app/Actions`: reglas de circulación reutilizables.
- `app/Models`: modelos Eloquent y relaciones PostgreSQL.
- `app/Policies` y `app/Http/Middleware`: autorización por rol.
- `resources/views/pages`: pantallas Livewire.
- `database/seeders`: usuarios demo e importación del acervo.
- `tests/Feature`: autenticación, autorización, catálogo, seguridad y circulación.
