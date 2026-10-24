<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

#[Signature('supabase:prepare
    {--connection= : Nombre de la conexión; por defecto usa la conexión de la aplicación}
    {--schema= : Esquema privado a crear; por defecto usa la conexión}')]
#[Description('Prepara el esquema privado de la aplicación en Supabase')]
class SupabasePrepare extends Command
{
    public function handle(): int
    {
        $connectionName = (string) ($this->option('connection') ?: config('database.default'));
        $connection = DB::connection($connectionName);
        $schema = (string) ($this->option('schema') ?: config(
            "database.connections.{$connectionName}.search_path",
            'biblioteca',
        ));
        $schema = trim(explode(',', $schema, 2)[0]);

        if ($connection->getDriverName() !== 'pgsql') {
            $this->error('La conexión indicada debe utilizar el driver pgsql.');

            return self::FAILURE;
        }

        if (! preg_match('/^[a-z_][a-z0-9_]*$/', $schema)) {
            $this->error('El nombre del esquema solo puede contener letras minúsculas, números y guiones bajos.');

            return self::FAILURE;
        }

        try {
            $connection->statement('create schema if not exists "'.$schema.'"');
        } catch (QueryException $exception) {
            $this->error("No fue posible crear el esquema [{$schema}].");
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Esquema [{$schema}] listo en la conexión [{$connectionName}].");

        return self::SUCCESS;
    }
}
