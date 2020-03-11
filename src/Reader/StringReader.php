<?php declare(strict_types=1);

namespace Tale\Reader;

use Tale\ReaderInterface;

final class StringReader implements ReaderInterface
{
    private string $string;
    private int $nextConsumeLength = 0;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function eof(): bool
    {
        return \strlen($this->string) <= 0;
    }

    public function peek(int $length = 1): string
    {
        if ($this->eof() || \strlen($this->string) < $length) {
            $this->nextConsumeLength = 0;
            return '';
        }
        $this->nextConsumeLength = $length;
        if ($length === 1) {
            return $this->string[0];
        }
        return \substr($this->string, 0, $length);
    }

    public function consume(int $length = 0): string
    {
        $length = $length !== 0 ? $length : $this->nextConsumeLength;
        if ($length === 0 || $this->eof()) {
            return '';
        }
        $consumedBytes = substr($this->string, 0, $length);
        $this->string = substr($this->string, $length);
        $this->nextConsumeLength = 0;
        return $consumedBytes;
    }
}
