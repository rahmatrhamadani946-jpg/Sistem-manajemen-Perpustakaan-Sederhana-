<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'isbn',
        'published_year',
        'price',
        'stock',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** Cari berdasarkan judul buku atau nama penulis. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhereHas('author', fn (Builder $a) => $a->where('name', 'like', "%{$term}%"));
        });
    }

    /** Filter berdasarkan kategori. */
    public function scopeInCategory(Builder $query, $categoryId): Builder
    {
        if (blank($categoryId)) {
            return $query;
        }

        return $query->whereHas('categories', fn (Builder $c) => $c->where('categories.id', $categoryId));
    }

    /** Hanya buku yang stoknya tersedia (> 0). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    /** Buku yang stoknya menipis (< batas). */
    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query->where('stock', '<', $threshold);
    }
}

