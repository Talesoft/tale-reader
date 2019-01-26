<?php declare(strict_types=1);

namespace Tale;

interface ReaderInterface
{
    public function eof(): bool;
    public function peek(int $length = 1): string;
    public function consume(int $length = 0): string;
}
