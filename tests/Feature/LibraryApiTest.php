<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_crud_and_cascade_confirmation(): void
    {
        $this->postJson('/api/authors', ['name' => 'Andi', 'email' => 'andi@example.com'])
            ->assertCreated();

        $author = Author::first();
        Book::factory()->count(3)->create(['author_id' => $author->id]);

        $this->getJson("/api/authors/{$author->id}")
            ->assertOk()
            ->assertJsonCount(3, 'books');

        $this->putJson("/api/authors/{$author->id}", ['name' => 'Andi W'])
            ->assertOk()
            ->assertJsonPath('name', 'Andi W');

        // Tanpa konfirmasi -> ditolak
        $this->deleteJson("/api/authors/{$author->id}")->assertStatus(409);
        $this->assertDatabaseCount('books', 3);

        // Dengan konfirmasi -> buku ikut terhapus
        $this->deleteJson("/api/authors/{$author->id}?confirm=true")->assertOk();
        $this->assertDatabaseCount('authors', 0);
        $this->assertDatabaseCount('books', 0);
    }

    public function test_category_create_and_delete(): void
    {
        $this->postJson('/api/categories', ['name' => 'Teknologi Informasi'])
            ->assertCreated()
            ->assertJsonPath('slug', 'teknologi-informasi');

        $category = Category::first();
        $this->deleteJson("/api/categories/{$category->id}")->assertOk();
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_book_crud_with_categories(): void
    {
        $author = Author::factory()->create();
        $cats = Category::factory()->count(2)->create();

        $res = $this->postJson('/api/books', [
            'title' => 'Belajar Laravel',
            'isbn' => '9786020000001',
            'published_year' => 2024,
            'price' => 120000,
            'stock' => 10,
            'author_id' => $author->id,
            'category_ids' => $cats->pluck('id')->all(),
        ])->assertCreated();

        $id = $res->json('id');
        $this->assertCount(2, $res->json('categories'));

        $this->putJson("/api/books/{$id}", ['stock' => 2])
            ->assertOk()
            ->assertJsonPath('stock', 2);

        $this->getJson("/api/books/{$id}")->assertOk()->assertJsonPath('author.id', $author->id);

        $this->deleteJson("/api/books/{$id}")->assertOk();
        $this->assertDatabaseCount('books', 0);
    }

    public function test_search_filter_stats_and_low_stock(): void
    {
        $penulis = Author::factory()->create(['name' => 'Budi Santoso']);
        $other = Author::factory()->create(['name' => 'Citra Lestari']);
        $cat = Category::factory()->create();

        $a = Book::factory()->create(['title' => 'Pemrograman PHP', 'author_id' => $penulis->id, 'stock' => 10]);
        Book::factory()->create(['title' => 'Basis Data', 'author_id' => $other->id, 'stock' => 0]);
        Book::factory()->create(['title' => 'Jaringan', 'author_id' => $other->id, 'stock' => 3]);
        $a->categories()->attach($cat->id);

        // cari judul
        $this->getJson('/api/books?q=PHP')->assertJsonCount(1, 'data');
        // cari nama penulis
        $this->getJson('/api/books?q=Citra')->assertJsonCount(2, 'data');
        // filter kategori
        $this->getJson("/api/books?category_id={$cat->id}")->assertJsonCount(1, 'data');
        // filter stok tersedia
        $this->getJson('/api/books?in_stock=1')->assertJsonCount(2, 'data');
        // statistik
        $this->getJson('/api/books/stats')->assertOk()->assertExactJson([
            'total_books' => 3,
            'total_authors' => 2,
            'total_categories' => 1,
        ]);
        // stok < 5
        $this->getJson('/api/books/low-stock')->assertOk()->assertJsonCount(2);
    }
}
