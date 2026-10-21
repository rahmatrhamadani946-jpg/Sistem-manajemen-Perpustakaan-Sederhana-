<?php

namespace Database\Factories;

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'isbn' => fake()->unique()->isbn13(),
            'published_year' => fake()->numberBetween(1990, (int) date('Y')),
            'price' => fake()->randomFloat(2, 50000, 500000),
            'stock' => fake()->numberBetween(0, 100),
            'author_id' => Author::factory(),
        ];
    }
}
