<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->slug().'.txt';

        return [
            'title' => fake()->sentence(3),
            'original_filename' => $filename,
            'path' => 'documents/'.$filename,
            'mime_type' => 'text/plain',
            'status' => DocumentStatus::Pending,
        ];
    }
}
