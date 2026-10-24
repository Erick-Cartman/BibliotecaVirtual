<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('supabase:check
    {--connection=supabase : Nombre de la conexión de base de datos a comprobar}')]
#[Description('Comprueba la conexión y el esquema PostgreSQL de Supabase')]
class SupabaseCheck extends Command
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'users',
        'books',
        'copies',
        'loans',
        'reservations',
        'audit_logs',
        'migrations',
    ];

    public function handle(): int
    {
        $connection = (string) $this->option('connection');

        try {
            $database = DB::connection($connection);
            $database->getPdo();
            $server = $database->selectOne(
                'select current_database() as database, current_user as user, current_schema() as schema, version() as version'
            );
        } catch (QueryException $exception) {
            $this->error("No fue posible conectar con la conexión [{$connection}].");
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Conexión [{$connection}] establecida.");
        $this->line('Base de datos: '.($server->database ?? 'desconocida'));
        $this->line('Usuario: '.($server->user ?? 'desconocido'));
        $this->line('Esquema activo: '.($server->schema ?? 'desconocido'));
        $this->line('Servidor: '.str((string) ($server->version ?? 'desconocido'))->before(' - '));

        $missingTables = collect(self::REQUIRED_TABLES)
            ->reject(fn (string $table): bool => Schema::connection($connection)->hasTable($table))
            ->values();

        if ($missingTables->isNotEmpty()) {
            $this->error('Faltan tablas: '.$missingTables->implode(', '));

            return self::FAILURE;
        }

        $this->info('Esquema requerido disponible.');

        foreach (self::REQUIRED_TABLES as $table) {
            $this->line(sprintf('%-16s %d registros', $table.':', $database->table($table)->count()));
        }

        return self::SUCCESS;
    }
}
