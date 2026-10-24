<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $schema = $this->applicationSchema();

        if ($schema === 'public') {
            return;
        }

        $connection->statement('create schema if not exists "'.$schema.'"');
    }

    public function down(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $schema = $this->applicationSchema();

        if ($schema !== 'public') {
            $connection->statement('drop schema if exists "'.$schema.'" cascade');
        }
    }

    private function applicationSchema(): string
    {
        $connectionName = (string) config('database.default');
        $searchPath = config("database.connections.{$connectionName}.search_path", 'public');
        $schema = trim(explode(',', (string) $searchPath, 2)[0]);

        if (! preg_match('/^[a-z_][a-z0-9_]*$/', $schema)) {
            throw new RuntimeException('El esquema PostgreSQL configurado no es válido.');
        }

        return $schema;
    }
};
