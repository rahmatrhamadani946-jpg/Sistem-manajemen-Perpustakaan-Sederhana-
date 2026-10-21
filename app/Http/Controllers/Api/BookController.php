<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * Daftar buku dengan penulis dan kategori.
     * Query string opsional:
     *   ?q=kata        cari berdasarkan judul atau nama penulis
     *   ?category_id=1 filter berdasarkan kategori
     *   ?in_stock=1    hanya buku dengan stok > 0
     */
    public function index(Request $request): JsonResponse
    {
        $books = Book::with('author', 'categories')
            ->search($request->query('q'))
            ->inCategory($request->query('category_id'))
            ->when($request->boolean('in_stock'), fn ($q) => $q->available())
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return response()->json($books);
    }

    /** Tambah buku baru, dengan memilih penulis dan kategori. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'published_year' => 'required|integer|min:1900|max:' . date('Y'),
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'author_id' => 'required|exists:authors,id',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $book = Book::create(collect($validated)->except('category_ids')->all());

        if (! empty($validated['category_ids'])) {
            $book->categories()->sync($validated['category_ids']);
        }

        return response()->json($book->load('author', 'categories'), 201);
    }

    /** Detail buku. */
    public function show(Book $book): JsonResponse
    {
        return response()->json($book->load('author', 'categories'));
    }

    /** Update data buku (termasuk stok dan kategori). */
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'isbn' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($book->id)],
            'published_year' => 'sometimes|required|integer|min:1900|max:' . date('Y'),
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'author_id' => 'sometimes|required|exists:authors,id',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $book->update(collect($validated)->except('category_ids')->all());

        if (array_key_exists('category_ids', $validated)) {
            $book->categories()->sync($validated['category_ids']);
        }

        return response()->json($book->load('author', 'categories'));
    }

    /** Hapus buku. */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }

    /** Statistik: total buku, penulis, kategori. */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_books' => Book::count(),
            'total_authors' => Author::count(),
            'total_categories' => Category::count(),
        ]);
    }

    /** Daftar buku yang stoknya menipis/habis (stok < 5). */
    public function lowStock(): JsonResponse
    {
        return response()->json(
            Book::with('author')->lowStock(5)->orderBy('stock')->get()
        );
    }
}
