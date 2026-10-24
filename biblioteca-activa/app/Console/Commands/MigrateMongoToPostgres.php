<?php

namespace App\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

class MigrateMongoToPostgres extends Command
{
    protected $signature = 'database:migrate-mongodb-to-postgres
        {--source-connection=mongodb : Conexión MongoDB de origen}
        {--target-connection=pgsql : Conexión PostgreSQL de destino}
        {--dry-run : Solo cuenta y valida documentos, sin escribir en PostgreSQL}';

    protected $description = 'Migra las colecciones de la biblioteca desde MongoDB a PostgreSQL';

    /** @var list<string> */
    private const COLLECTIONS = [
        'users',
        'books',
        'copies',
        'loans',
        'reservations',
        'audit_logs',
    ];

    public function handle(): int
    {
        if (! extension_loaded('mongodb')) {
            $this->error('Esta migración requiere la extensión mongodb de PHP y solo está disponible en desarrollo.');

            return self::FAILURE;
        }

        $source = DB::connection((string) $this->option('source-connection'));
        $target = DB::connection((string) $this->option('target-connection'));

        if ($source->getDriverName() !== 'mongodb') {
            $this->error('La conexión de origen debe usar el driver mongodb.');

            return self::FAILURE;
        }

        if ($target->getDriverName() !== 'pgsql') {
            $this->error('La conexión de destino debe usar el driver pgsql.');

            return self::FAILURE;
        }

        $documents = $this->readDocuments($source);
        $sourceCounts = collect($documents)->mapWithKeys(
            fn (array $items, string $collection): array => [$collection => count($items)],
        );

        $this->table(
            ['Colección', 'Documentos'],
            $sourceCounts->map(
                fn (int $count, string $collection): array => [$collection, $count],
            )->values()->all(),
        );

        if ($this->option('dry-run')) {
            $this->info('Simulación completada: no se modificó PostgreSQL.');

            return self::SUCCESS;
        }

        foreach (self::COLLECTIONS as $collection) {
            if ($target->table($collection)->count() > 0) {
                $this->error(
                    "La tabla {$collection} no está vacía. "
                    .'La migración se detiene para evitar sobreescribir datos.',
                );

                return self::FAILURE;
            }
        }

        $target->transaction(function () use ($documents, $target): void {
            foreach (self::COLLECTIONS as $collection) {
                $rows = $this->mapRows($collection, $documents[$collection]);

                foreach (array_chunk($rows, 500) as $chunk) {
                    if ($chunk !== []) {
                        $target->table($collection)->insert($chunk);
                    }
                }
            }
        });

        $this->table(
            ['Tabla PostgreSQL', 'Origen', 'Destino', 'Estado'],
            collect(self::COLLECTIONS)->map(function (string $collection) use ($sourceCounts, $target): array {
                $sourceCount = $sourceCounts->get($collection, 0);
                $targetCount = $target->table($collection)->count();

                return [
                    $collection,
                    $sourceCount,
                    $targetCount,
                    $sourceCount === $targetCount ? 'OK' : 'ERROR',
                ];
            })->all(),
        );

        $mismatches = collect(self::COLLECTIONS)->filter(
            fn (string $collection): bool => $sourceCounts->get($collection, 0)
                !== $target->table($collection)->count(),
        );

        if ($mismatches->isNotEmpty()) {
            $this->error('La validación de conteos no coincide.');

            return self::FAILURE;
        }

        $this->info('Migración completada y validada.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function readDocuments(Connection $source): array
    {
        /** @var \MongoDB\Laravel\Connection $source */
        $database = $source->getDatabase();
        $documents = [];

        foreach (self::COLLECTIONS as $collection) {
            $cursor = $database
                ->selectCollection($collection)
                ->find([], ['sort' => ['_id' => 1]]);

            $documents[$collection] = [];
            foreach ($cursor as $document) {
                $documents[$collection][] = (array) $document;
            }
        }

        return $documents;
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @return list<array<string, mixed>>
     */
    private function mapRows(string $collection, array $documents): array
    {
        return array_map(
            fn (array $document): array => match ($collection) {
                'users' => $this->mapUser($document),
                'books' => $this->mapBook($document),
                'copies' => $this->mapCopy($document),
                'loans' => $this->mapLoan($document),
                'reservations' => $this->mapReservation($document),
                'audit_logs' => $this->mapAuditLog($document),
            },
            $documents,
        );
    }

    /** @param array<string, mixed> $document */
    private function mapUser(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'name' => (string) ($document['name'] ?? ''),
            'first_name' => $this->nullableString($document['first_name'] ?? null),
            'last_name' => $this->nullableString($document['last_name'] ?? null),
            'email' => (string) ($document['email'] ?? ''),
            'email_verified_at' => $this->date($document['email_verified_at'] ?? null),
            'password' => (string) ($document['password'] ?? ''),
            'avatar_path' => $this->nullableString($document['avatar_path'] ?? null),
            'student_code' => $this->nullableString($document['student_code'] ?? null),
            'role' => (string) ($document['role'] ?? ''),
            'status' => (string) ($document['status'] ?? ''),
            'remember_token' => $this->nullableString($document['remember_token'] ?? null),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $document */
    private function mapBook(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'source_key' => (string) ($document['source_key'] ?? ''),
            'title' => (string) ($document['title'] ?? ''),
            'authors' => $this->json($document['authors'] ?? []),
            'isbn' => $this->nullableString($document['isbn'] ?? null),
            'publisher' => $this->nullableString($document['publisher'] ?? null),
            'publication_year' => $this->nullableInteger($document['publication_year'] ?? null),
            'dewey_code' => $this->nullableString($document['dewey_code'] ?? null),
            'learning_unit' => $this->nullableString($document['learning_unit'] ?? null),
            'program' => (string) ($document['program'] ?? ''),
            'search_text' => $this->nullableString($document['search_text'] ?? null),
            'total_copies' => (int) ($document['total_copies'] ?? 0),
            'available_copies' => (int) ($document['available_copies'] ?? 0),
            'cover_url' => $this->nullableString($document['cover_url'] ?? null),
            'source' => $this->jsonOrNull($document['source'] ?? null),
            'active' => (bool) ($document['active'] ?? true),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $document */
    private function mapCopy(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'book_id' => $this->id($document['book_id'] ?? null),
            'barcode' => (string) ($document['barcode'] ?? ''),
            'location' => $this->nullableString($document['location'] ?? null),
            'condition' => $this->nullableString($document['condition'] ?? null),
            'status' => (string) ($document['status'] ?? ''),
            'active' => (bool) ($document['active'] ?? true),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $document */
    private function mapLoan(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'copy_id' => $this->id($document['copy_id'] ?? null),
            'book_id' => $this->id($document['book_id'] ?? null),
            'user_id' => $this->id($document['user_id'] ?? null),
            'operator_id' => $this->nullableId($document['operator_id'] ?? null),
            'status' => (string) ($document['status'] ?? ''),
            'loaned_at' => $this->date($document['loaned_at'] ?? null),
            'due_at' => $this->date($document['due_at'] ?? null),
            'returned_at' => $this->date($document['returned_at'] ?? null),
            'renewals' => (int) ($document['renewals'] ?? 0),
            'book_title' => (string) ($document['book_title'] ?? ''),
            'copy_barcode' => (string) ($document['copy_barcode'] ?? ''),
            'user_name' => (string) ($document['user_name'] ?? ''),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $document */
    private function mapReservation(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'book_id' => $this->id($document['book_id'] ?? null),
            'user_id' => $this->id($document['user_id'] ?? null),
            'status' => (string) ($document['status'] ?? ''),
            'reserved_at' => $this->date($document['reserved_at'] ?? null),
            'expires_at' => $this->date($document['expires_at'] ?? null),
            'book_title' => (string) ($document['book_title'] ?? ''),
            'student_name' => (string) ($document['student_name'] ?? ''),
            'student_code' => $this->nullableString($document['student_code'] ?? null),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    /** @param array<string, mixed> $document */
    private function mapAuditLog(array $document): array
    {
        return [
            'id' => $this->id($document['_id'] ?? null),
            'actor_id' => $this->nullableId($document['actor_id'] ?? null),
            'action' => (string) ($document['action'] ?? ''),
            'entity_type' => (string) ($document['entity_type'] ?? ''),
            'entity_id' => (string) ($document['entity_id'] ?? ''),
            'summary' => (string) ($document['summary'] ?? ''),
            'metadata' => $this->jsonOrNull($document['metadata'] ?? null),
            'created_at' => $this->date($document['created_at'] ?? null),
            'updated_at' => $this->date($document['updated_at'] ?? null),
        ];
    }

    private function id(mixed $value): string
    {
        $id = (string) $value;

        if ($id === '') {
            throw new RuntimeException('Se encontró un documento sin identificador.');
        }

        return $id;
    }

    private function nullableId(mixed $value): ?string
    {
        return $value === null ? null : $this->id($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s.uP');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.uP');
        }

        return (string) $value;
    }

    private function json(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function jsonOrNull(mixed $value): ?string
    {
        return $value === null ? null : $this->json($value);
    }
}
