<?php declare(strict_types=1);

namespace Tale\Reader;

use Tale\Reader\Text\Location;
use Tale\ReaderInterface;

final class TextReader implements ReaderInterface
{
    public const LINE_DELIMITER_LF = "\n";
    public const LINE_DELIMITER_CR = "\r";
    public const LINE_DELIMITER_CRLF = "\r\n";
    public const LINE_DELIMITER_SYSTEM = \PHP_EOL;

    /** @var ReaderInterface */
    private $reader;
    private $currentLine = 0;
    private $currentOffset = 0;
    private $lineDelimiter;

    public function __construct(ReaderInterface $reader, string $lineDelimiter = self::LINE_DELIMITER_LF)
    {
        $this->reader = $reader;
        $this->lineDelimiter = $lineDelimiter;
    }

    /**
     * @return int
     */
    public function getCurrentLine(): int
    {
        return $this->currentLine;
    }

    /**
     * @return int
     */
    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }

    public function getCurrentLocation(): Location
    {
        return new Location($this->currentLine, $this->currentOffset);
    }

    /**
     * @return string
     */
    public function getLineDelimiter(): string
    {
        return $this->lineDelimiter;
    }

    public function eof(): bool
    {
        return $this->reader->eof();
    }

    public function peek(int $length = 1): string
    {
        return $this->reader->peek($length);
    }

    public function consume(int $length = 0): string
    {
        $bytes = $this->reader->consume($length);
        $newLines = substr_count($bytes, $this->lineDelimiter);
        if (!$newLines) {
            $this->currentOffset += \strlen($bytes);
            return $bytes;
        }

        $this->currentLine += $newLines;
        $this->currentOffset = \strlen($bytes) - (
            strrpos($bytes, $this->lineDelimiter) + \strlen($this->lineDelimiter)
        );
        return $bytes;
    }

    public function read(int $length = 1): string
    {
        $bytes = $this->peek($length);
        $this->consume();
        return $bytes;
    }

    public function readWhile(callable $callback, int $peekLength = 1, bool $inclusive = false): string
    {
        $bytes = '';
        while (!$this->eof() && ($peekedBytes = $this->peek($peekLength)) !== '' && $callback($peekedBytes)) {
            $bytes .= $peekedBytes;
            $this->consume();
        }
        if ($inclusive && !$this->eof()) {
            $this->consume();
        }
        return $bytes;
    }

    public function readUntil(callable $callback, int $peekLength = 1, bool $inclusive = false): string
    {
        return $this->readWhile(function (string $bytes) use ($callback) {
            return !$callback($bytes);
        }, $peekLength, $inclusive);
    }

    public function peekText(string $text): bool
    {
        return $this->peek(\strlen($text)) === $text;
    }

    public function peekNewLine(): bool
    {
        return $this->peekText($this->lineDelimiter);
    }

    public function readLine(): string
    {
        return trim($this->readUntil(function (string $bytes) {
            return $bytes === $this->lineDelimiter;
        }, \strlen($this->lineDelimiter), true), self::LINE_DELIMITER_CRLF);
    }

    public function peekSpace(): bool
    {
        return ctype_space($this->peek());
    }

    public function peekAlpha(): bool
    {
        return ctype_alpha($this->peek());
    }

    public function peekDigit(): bool
    {
        return ctype_digit($this->peek());
    }

    public function peekAlphaNumeric(): bool
    {
        return ctype_alnum($this->peek());
    }

    public function readSpaces(): string
    {
        return $this->readWhile('ctype_space');
    }

    public function readNonSpaces(): string
    {
        return $this->readUntil('ctype_space');
    }

    public function readAlpha(): string
    {
        return $this->readWhile('ctype_alpha');
    }

    public function readNonAlpha(): string
    {
        return $this->readUntil('ctype_alpha');
    }

    public function readDigits(): string
    {
        return $this->readWhile('ctype_digit');
    }

    public function readNonDigits(): string
    {
        return $this->readUntil('ctype_digit');
    }

    public function readAlphaNumeric(): string
    {
        return $this->readWhile('ctype_alnum');
    }

    public function readNonAlphaNumeric(): string
    {
        return $this->readUntil('ctype_alnum');
    }
}
