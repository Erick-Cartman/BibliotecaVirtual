<?php

namespace Tests\Feature;

use App\Models\Book;
use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_homepage_shows_the_3d_viewer_when_an_active_book_has_no_external_cover(): void
    {
        $book = Book::factory()->create([
            'title' => 'Libro destacado sin portada externa',
            'cover_url' => null,
            'active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-book-viewer', false)
            ->assertSee('book-3d', false)
            ->assertDontSee('Pausar giro')
            ->assertSee($book->title);
    }

    public function test_homepage_uses_el_principito_as_the_default_3d_book(): void
    {
        Book::factory()->create([
            'source_key' => 'demo-el-principito',
            'title' => 'El Principito',
            'cover_url' => 'https://covers.openlibrary.org/b/isbn/9780156012195-L.jpg?default=false',
            'active' => true,
        ]);
        Book::factory()->create([
            'title' => 'Otra portada disponible',
            'cover_url' => 'https://example.com/otra-portada.jpg',
            'active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee('El Principito');
    }
}
