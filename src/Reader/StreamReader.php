<?php declare(strict_types=1);

namespace Tale\Reader;

use Tale\ReaderInterface;
use Psr\Http\Message\StreamInterface;

final class StreamReader implements ReaderInterface
{
    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var int
     */
    private $bufferSize;

    /**
     * @var int
     */
    private $nextConsumeLength = 0;

    public function __construct(StreamInterface $stream, int $bufferSize = 1024)
    {
        $this->stream = $stream;
        $this->bufferSize = $bufferSize;
    }

    public function eof(): bool
    {
        //Expand buffer by one byte, most reliable method I guess (it has an eof check inbuilt)
        $this->expandBuffer(1);
        return \strlen($this->buffer) <= 0; //There was no way to expand the buffer, basically
    }

    public function peek(int $length = 1): string
    {
        $this->expandBuffer($length);
        if ($this->eof() || \strlen($this->buffer) < $length) {
            $this->nextConsumeLength = 0;
            return '';
        }
        $this->nextConsumeLength = $length;
        if ($length === 1) {
            return $this->buffer[0];
        }
        return \substr($this->buffer, 0, $length);
    }

    public function consume(int $length = 0): string
    {
        $length = $length !== 0 ? $length : $this->nextConsumeLength;
        if ($length === 0 || $this->eof()) {
            return '';
        }
        $this->expandBuffer($length);
        $consumedBytes = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, $length);
        $this->nextConsumeLength = 0;
        return $consumedBytes;
    }

    private function expandBuffer(int $length): void
    {
        if (\strlen($this->buffer) >= $length || $this->stream->eof()) {
            return;
        }
        $this->buffer .= $this->stream->read($this->bufferSize);
    }
}
