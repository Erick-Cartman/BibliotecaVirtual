<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    public function test_librarian_can_edit_book_metadata(): void
    {
        $librarian = User::factory()->librarian()->create();
        $book = Book::factory()->create([
            'title' => 'Título original',
            'authors' => ['Autor original'],
            'search_text' => 'titulo original autor original',
        ]);

        Livewire::actingAs($librarian)
            ->test('pages::catalog')
            ->call('viewBook', (string) $book->getKey())
            ->assertSee('Editar datos')
            ->call('editBook', (string) $book->getKey())
            ->assertSet('editTitle', 'Título original')
            ->set('editTitle', 'Nuevo título de investigación')
            ->set('editAuthors', 'María Investigadora')
            ->set('editIsbn', '9781234567890')
            ->set('editPublisher', 'Editorial Universitaria')
            ->set('editPublicationYear', '2025')
            ->set('editDeweyCode', '001.42')
            ->set('editLearningUnit', 'Metodología')
            ->set('editProgram', 'Investigación')
            ->set('editCoverUrl', 'https://example.com/portadas/investigacion.jpg')
            ->call('updateBook')
            ->assertHasNoErrors()
            ->assertSet('editingBookId', null)
            ->assertSee('Los datos del libro se actualizaron correctamente.');

        $updatedBook = $book->refresh();
        $this->assertSame('Nuevo título de investigación', $updatedBook->title);
        $this->assertSame(['María Investigadora'], $updatedBook->authors);
        $this->assertSame('9781234567890', $updatedBook->isbn);
        $this->assertSame('Editorial Universitaria', $updatedBook->publisher);
        $this->assertSame(2025, $updatedBook->publication_year);
        $this->assertSame('001.42', $updatedBook->dewey_code);
        $this->assertSame('Metodología', $updatedBook->learning_unit);
        $this->assertSame('Investigación', $updatedBook->program);
        $this->assertSame('https://example.com/portadas/investigacion.jpg', $updatedBook->cover_url);
        $this->assertStringContainsString('nuevo titulo de investigacion', $updatedBook->search_text);
    }

    public function test_book_editing_requires_a_title_and_author(): void
    {
        $librarian = User::factory()->librarian()->create();
        $book = Book::factory()->create(['title' => 'Título sin cambios']);

        Livewire::actingAs($librarian)
            ->test('pages::catalog')
            ->call('editBook', (string) $book->getKey())
            ->set('editTitle', '')
            ->set('editAuthors', '')
            ->call('updateBook')
            ->assertHasErrors(['editTitle' => 'required', 'editAuthors' => 'required'])
            ->assertSee('El título es obligatorio.')
            ->assertSee('Registra al menos un autor.');

        $this->assertSame('Título sin cambios', $book->refresh()->title);
    }

    public function test_student_cannot_edit_book_metadata(): void
    {
        $student = User::factory()->create();
        $book = Book::factory()->create();

        Livewire::actingAs($student)
            ->test('pages::catalog')
            ->assertDontSee('Editar datos')
            ->call('editBook', (string) $book->getKey())
            ->assertForbidden();
    }

    public function test_admin_can_open_a_book_record_and_start_editing(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create(['title' => 'Administración de bibliotecas']);

        Livewire::actingAs($admin)
            ->test('pages::catalog')
            ->call('viewBook', (string) $book->getKey())
            ->assertSee('Editar datos')
            ->call('editBook', (string) $book->getKey())
            ->assertSet('editingBookId', (string) $book->getKey())
            ->assertSet('editTitle', 'Administración de bibliotecas');
    }

    public function test_student_can_open_the_complete_book_record_in_read_only_mode(): void
    {
        $student = User::factory()->create();
        $book = Book::factory()->create([
            'title' => 'Diseño de sistemas',
            'authors' => ['Elena Torres'],
            'isbn' => '9781234567897',
            'publisher' => 'Editorial Académica',
            'publication_year' => 2024,
            'dewey_code' => '005.12',
            'learning_unit' => 'Ingeniería de software',
        ]);

        Livewire::actingAs($student)
            ->test('pages::catalog')
            ->call('viewBook', (string) $book->getKey())
            ->assertSet('selectedBookId', (string) $book->getKey())
            ->assertSee('Ficha bibliográfica')
            ->assertSee('Editorial Académica')
            ->assertSee('Ingeniería de software')
            ->assertSee('9781234567897')
            ->assertDontSee('Editar datos');
    }

    public function test_catalog_uses_the_isbn_to_show_a_book_cover(): void
    {
        $student = User::factory()->create();
        Book::factory()->create([
            'title' => 'Libro con portada',
            'isbn' => '978-1-23456-789-7',
            'cover_url' => null,
        ]);

        $this->actingAs($student)->get('/catalogo')
            ->assertSee('https://covers.openlibrary.org/b/isbn/9781234567897-L.jpg?default=false', false)
            ->assertSee('Portada de Libro con portada');
    }

    public function test_catalog_shows_a_generic_cover_when_a_book_has_no_cover(): void
    {
        $student = User::factory()->create();
        Book::factory()->create([
            'title' => 'Libro sin portada',
            'isbn' => null,
            'cover_url' => null,
        ]);

        $this->actingAs($student)->get('/catalogo')
            ->assertOk()
            ->assertSee('Portada genérica')
            ->assertSee('Portada genérica de Libro sin portada');
    }

    public function test_student_reservation_is_sent_to_circulation_and_shown_in_their_catalog(): void
    {
        $student = User::factory()->create(['student_code' => 'EST-123']);
        $book = Book::factory()->create(['title' => 'Introducción a la administración']);

        Livewire::actingAs($student)
            ->test('pages::catalog')
            ->call('reserve', (string) $book->getKey())
            ->assertSee('Reservación enviada a Circulación')
            ->assertSee('Introducción a la administración')
            ->assertSee('Pendiente');

        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame((string) $student->getKey(), (string) $reservation->user_id);
        $this->assertSame((string) $book->getKey(), (string) $reservation->book_id);
        $this->assertSame('EST-123', $reservation->student_code);
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
    }

    public function test_authenticated_user_can_view_catalog_content(): void
    {
        $student = User::factory()->create();
        Book::factory()->create(['title' => 'Metodología de la investigación']);

        $this->actingAs($student)->get('/catalogo')
            ->assertOk()
            ->assertSee('Catálogo')
            ->assertSee('Metodología de la investigación');
    }

    public function test_catalog_escapes_untrusted_book_titles(): void
    {
        $student = User::factory()->create();
        Book::factory()->create(['title' => '<script>alert(1)</script>']);

        $this->actingAs($student)->get('/catalogo')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
