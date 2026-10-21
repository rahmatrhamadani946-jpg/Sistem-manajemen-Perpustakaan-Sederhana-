<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /** Daftar semua kategori. */
    public function index(): JsonResponse
    {
        return response()->json(Category::withCount('books')->orderBy('name')->get());
    }

    /** Tambah kategori baru (slug dibuat otomatis dari nama bila tidak dikirim). */
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'slug' => $request->input('slug') ?: Str::slug((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
        ]);

        return response()->json(Category::create($validated), 201);
    }

    /** Detail kategori beserta bukunya. */
    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load('books'));
    }

    /** Hapus kategori (baris pivot ikut terhapus; buku tetap ada). */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}