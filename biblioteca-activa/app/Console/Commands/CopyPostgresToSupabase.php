<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

#[Signature('database:copy-postgres-to-supabase
    {--source-connection=pgsql : Conexión PostgreSQL local de origen}
    {--target-connection=supabase : Conexión PostgreSQL de Supabase}
    {--dry-run : Solo cuenta y valida registros, sin escribir en Supabase}')]
#[Description('Copia las tablas de PostgreSQL local a Supabase preservando sus identificadores')]
class CopyPostgresToSupabase extends Command
{
    /** @var list<string> */
    private const TABLES = [
        'users',
        'books',
        'copies',
        'loans',
        'reservations',
        'audit_logs',
    ];

    public function handle(): int
    {
        $source = DB::connection((string) $this->option('source-connection'));
        $target = DB::connection((string) $this->option('target-connection'));

        if ($source->getDriverName() !== 'pgsql' || $target->getDriverName() !== 'pgsql') {
            $this->error('El origen y el destino deben utilizar el driver pgsql.');

            return self::FAILURE;
        }

        $rowsByTable = $this->readRows($source);
        $sourceCounts = collect($rowsByTable)->mapWithKeys(
            fn (array $rows, string $table): array => [$table => count($rows)],
        );

        $this->table(
            ['Tabla', 'Registros de origen'],
            $sourceCounts->map(
                fn (int $count, string $table): array => [$table, $count],
            )->values()->all(),
        );

        if ($this->option('dry-run')) {
            $this->info('Simulación completada: no se modificó Supabase.');

            return self::SUCCESS;
        }

        foreach (self::TABLES as $table) {
            if ($target->table($table)->count() > 0) {
                $this->error(
                    "La tabla {$table} de destino no está vacía. "
                    .'La copia se detiene para evitar duplicados.',
                );

                return self::FAILURE;
            }
        }

        $target->transaction(function () use ($rowsByTable, $target): void {
            foreach (self::TABLES as $table) {
                foreach (array_chunk($rowsByTable[$table], 500) as $chunk) {
                    if ($chunk !== []) {
                        $target->table($table)->insert($chunk);
                    }
                }
            }
        });

        $this->table(
            ['Tabla', 'Origen', 'Supabase', 'Estado'],
            collect(self::TABLES)->map(function (string $table) use ($sourceCounts, $target): array {
                $sourceCount = $sourceCounts->get($table, 0);
                $targetCount = $target->table($table)->count();

                return [
                    $table,
                    $sourceCount,
                    $targetCount,
                    $sourceCount === $targetCount ? 'OK' : 'ERROR',
                ];
            })->all(),
        );

        $mismatches = collect(self::TABLES)->filter(
            fn (string $table): bool => $sourceCounts->get($table, 0)
                !== $target->table($table)->count(),
        );

        if ($mismatches->isNotEmpty()) {
            $this->error('La validación de conteos no coincide.');

            return self::FAILURE;
        }

        $this->info('Copia completada y validada.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function readRows(Connection $source): array
    {
        $rowsByTable = [];

        foreach (self::TABLES as $table) {
            $rowsByTable[$table] = $source->table($table)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        return $rowsByTable;
    }
}
