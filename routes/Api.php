<?php 

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

// Fitur tambahan: harus didaftarkan SEBELUM apiResource agar
// "stats" dan "low-stock" tidak dianggap sebagai {book}.
Route::get('books/stats', [BookController::class, 'stats']);
Route::get('books/low-stock', [BookController::class, 'lowStock']);

Route::apiResource('authors', AuthorController::class);
Route::apiResource('categories', CategoryController::class)->except(['update']);
Route::apiResource('books', BookController::class);