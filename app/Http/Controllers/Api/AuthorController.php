<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorController extends Controller
{
    /** Daftar semua penulis (beserta jumlah bukunya). */
    public function index(): JsonResponse
    {
        return response()->json(Author::withCount('books')->orderBy('name')->get());
    }

    /** Tambah penulis baru. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:authors,email',
            'bio' => 'nullable|string',
        ]);

        return response()->json(Author::create($validated), 201);
    }

    /** Detail penulis beserta daftar bukunya. */
    public function show(Author $author): JsonResponse
    {
        return response()->json($author->load('books'));
    }

    /** Update data penulis. */
    public function update(Request $request, Author $author): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('authors', 'email')->ignore($author->id)],
            'bio' => 'nullable|string',
        ]);

        $author->update($validated);

        return response()->json($author);
    }

    /**
     * Hapus penulis. Karena foreign key books.author_id memakai cascade,
     * semua buku milik penulis ikut terhapus. Klien wajib mengirim
     * ?confirm=true sebagai konfirmasi bahwa buku terkait ikut terhapus.
     */
    public function destroy(Request $request, Author $author): JsonResponse
    {
        $bookCount = $author->books()->count();

        if ($bookCount > 0 && ! $request->boolean('confirm')) {
            return response()->json([
                'message' => "Penulis memiliki {$bookCount} buku. Menghapus penulis akan menghapus semua bukunya. "
                    . 'Kirim ulang dengan ?confirm=true untuk melanjutkan.',
                'books_count' => $bookCount,
            ], 409);
        }

        $author->delete();

        return response()->json([
            'message' => 'Penulis berhasil dihapus',
            'books_deleted' => $bookCount,
        ]);
    }
}

