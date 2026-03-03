<?php

declare(strict_types=1);

namespace App\Models;

class Manufacturer
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $createdAt,
    ) {}

    /**
     * Hydrates a Manufacturer from a raw PDO associative row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): static
    {
        return new static(
            id:        (int)$row['id'],
            name:      (string)$row['name'],
            createdAt: (string)($row['created_at'] ?? ''),
        );
    }

    /**
     * Serializes the model to a plain array for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'created_at' => $this->createdAt,
        ];
    }
}
