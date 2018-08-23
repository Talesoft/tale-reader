<?php
declare(strict_types=1);

namespace Tale\Reader;

use Psr\Http\Message\StreamInterface;
use Tale\Reader;
use Tale\Reader\Text\Location;

class TextReader extends Reader
{
    public const LINE_DELIMITER_LF = "\n";
    public const LINE_DELIMITER_CR = "\r";
    public const LINE_DELIMITER_CRLF = "\r\n";
    public const LINE_DELIMITER_SYSTEM = \PHP_EOL;

    private $currentLine = 0;
    private $currentOffset = 0;
    private $lineDelimiter;

    public function __construct(
        StreamInterface $stream,
        string $lineDelimiter = self::LINE_DELIMITER_LF,
        int $bufferSize = 1024
    ) {
    
        parent::__construct($stream, $bufferSize);
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

    protected function onConsume(string $bytes): void
    {
        parent::onConsume($bytes);

        $newLines = substr_count($bytes, $this->lineDelimiter);
        if (!$newLines) {
            $this->currentOffset += \strlen($bytes);
            return;
        }

        $this->currentLine += $newLines;
        $this->currentOffset = \strlen($bytes) - (
            strrpos($bytes, $this->lineDelimiter) + \strlen($this->lineDelimiter)
        );
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
