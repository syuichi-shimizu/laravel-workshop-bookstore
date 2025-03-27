<?php

declare(strict_types=1);

namespace App\Service\Book;

use Brick\DateTime\LocalDate;

readonly class BookDetail
{
    /**
     * @param string[] $authors
     */
    public function __construct(
        public string $isbn,
        public ?string $title,
        public ?string $publisher,
        public ?LocalDate $publishDate,
        public array $authors = [],
    ) {}
}