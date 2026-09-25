<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ResolveBookCovers extends Command
{
    protected $signature = 'books:resolve-covers
        {--limit=0 : Limita la cantidad de libros a procesar}
        {--batch-size=25 : Cantidad de ISBN por búsqueda}
        {--delay=150 : Milisegundos de espera entre consultas}
        {--dry-run : No guarda cambios en MongoDB}
        {--write-seed-overrides : Actualiza database/seeders/data/book_covers.json}';

    protected $description = 'Busca portadas faltantes por ISBN o título y autor';

    /** @var array<string, string> */
    private array $resolvedCovers = [];

    public function handle(): int
    {
        $books = Book::query()
            ->where('active', true)
            ->where(function ($query): void {
                $query->whereNull('cover_url')->orWhere('cover_url', '');
            })
            ->get(['source_key', 'title', 'authors', 'isbn', 'cover_url']);

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $books = $books->take($limit);
        }

        $delay = max(0, (int) $this->option('delay'));
        $batchSize = max(1, min(50, (int) $this->option('batch-size')));
        $booksWithIsbn = $books->filter(fn (Book $book): bool => filled($this->normalizeIsbn($book->isbn)));
        $booksWithoutIsbn = $books->reject(fn (Book $book): bool => filled($this->normalizeIsbn($book->isbn)));

        $this->info(sprintf(
            'Libros pendientes: %d (%d con ISBN, %d sin ISBN).',
            $books->count(),
            $booksWithIsbn->count(),
            $booksWithoutIsbn->count(),
        ));

        $this->resolveIsbnCovers($booksWithIsbn, $batchSize, $delay);
        $this->resolveTitleCovers($booksWithoutIsbn, $delay);

        $saved = 0;
        if (! $this->option('dry-run')) {
            foreach ($books as $book) {
                $coverUrl = $this->resolvedCovers[(string) $book->source_key] ?? null;
                if (! $coverUrl) {
                    continue;
                }

                $book->update(['cover_url' => $coverUrl]);
                $saved++;
            }
        }

        if ($this->option('write-seed-overrides')) {
            $this->writeSeedOverrides();
        }

        $this->newLine();
        $this->info('Portadas encontradas: '.count($this->resolvedCovers));
        if ($this->option('dry-run')) {
            $this->comment('Modo simulación: no se guardaron cambios.');
        } else {
            $this->info('Portadas guardadas en MongoDB: '.$saved);
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Book>  $books
     */
    private function resolveIsbnCovers($books, int $batchSize, int $delay): void
    {
        $booksByIsbn = $books->mapWithKeys(function (Book $book): array {
            return [$this->normalizeIsbn($book->isbn) => $book];
        });

        foreach (array_chunk($booksByIsbn->keys()->all(), $batchSize) as $index => $isbns) {
            $query = 'isbn:('.implode(' OR ', $isbns).')';
            $response = $this->search([
                'q' => $query,
                'fields' => 'isbn,cover_i',
                'limit' => count($isbns) * 2,
            ]);

            if ($response === null) {
                $this->warn('No fue posible consultar el lote de ISBN '.($index + 1).'.');

                continue;
            }

            foreach ($response['docs'] ?? [] as $document) {
                $coverId = $document['cover_i'] ?? null;
                if (! $coverId) {
                    continue;
                }

                $coverUrl = 'https://covers.openlibrary.org/b/id/'.rawurlencode((string) $coverId).'-L.jpg';
                foreach ($document['isbn'] ?? [] as $documentIsbn) {
                    $normalizedIsbn = $this->normalizeIsbn($documentIsbn);
                    $book = $booksByIsbn->get($normalizedIsbn);
                    if (! $book) {
                        continue;
                    }

                    $this->resolvedCovers[(string) $book->source_key] = $coverUrl;
                }
            }

            $this->line(sprintf(
                'ISBN lote %d: %d/%d portadas acumuladas.',
                $index + 1,
                count($this->resolvedCovers),
                $books->count(),
            ));
            $this->pause($delay);
        }
    }

    /**
     * @param  Collection<int, Book>  $books
     */
    private function resolveTitleCovers($books, int $delay): void
    {
        foreach ($books->values() as $index => $book) {
            $response = $this->search([
                'title' => $book->title,
                'author' => $book->authorLine(),
                'fields' => 'title,author_name,cover_i',
                'limit' => 10,
            ]);

            if ($response !== null) {
                foreach ($response['docs'] ?? [] as $document) {
                    if (! ($document['cover_i'] ?? null) || ! $this->isLikelyMatch($book, $document)) {
                        continue;
                    }

                    $this->resolvedCovers[(string) $book->source_key] = 'https://covers.openlibrary.org/b/id/'.rawurlencode((string) $document['cover_i']).'-L.jpg';
                    break;
                }
            }

            $this->line(sprintf(
                'Búsqueda por título %d/%d: %s',
                $index + 1,
                $books->count(),
                $book->title,
            ));
            $this->pause($delay);
        }
    }

    /** @param array<string, mixed> $parameters */
    private function search(array $parameters): ?array
    {
        try {
            $response = $this->httpClient()->get('https://openlibrary.org/search.json', $parameters);
        } catch (\Throwable $exception) {
            $this->warn('Consulta de portada fallida: '.$exception->getMessage());

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function httpClient(): PendingRequest
    {
        $options = [];
        $localCaBundle = dirname(base_path()).'/.venv/Lib/site-packages/pip/_vendor/certifi/cacert.pem';

        if (File::exists($localCaBundle)) {
            $options['verify'] = $localCaBundle;
        }

        return Http::withOptions($options)->acceptJson()->timeout(15)->retry(2, 300);
    }

    private function pause(int $delay): void
    {
        if ($delay > 0) {
            usleep($delay * 1000);
        }
    }

    private function normalizeIsbn(mixed $isbn): string
    {
        $normalized = preg_replace('/[^0-9X]/i', '', (string) $isbn);

        return in_array(strlen($normalized), [10, 13], true) ? strtoupper($normalized) : '';
    }

    /** @param array<string, mixed> $document */
    private function isLikelyMatch(Book $book, array $document): bool
    {
        $targetTitle = $this->normalizeText($book->title);
        $documentTitle = $this->normalizeText($document['title'] ?? '');
        if ($targetTitle === '' || $documentTitle === '') {
            return false;
        }

        similar_text($targetTitle, $documentTitle, $similarity);
        if ($similarity >= 65 || str_contains($documentTitle, $targetTitle) || str_contains($targetTitle, $documentTitle)) {
            return true;
        }

        return false;
    }

    private function normalizeText(mixed $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function writeSeedOverrides(): void
    {
        $path = database_path('seeders/data/book_covers.json');
        $existing = File::exists($path)
            ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)
            : [];
        $overrides = array_replace($existing, $this->resolvedCovers);
        ksort($overrides);

        File::put($path, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }
}
