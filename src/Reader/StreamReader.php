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

    /**
     * creates a new streamReader instance.
     *
     * @param StreamInterface $stream a PSR-7 stream to read from.
     * @param int $bufferSize internal buffer size to use.
     */
    public function __construct(StreamInterface $stream, int $bufferSize = 1024)
    {
        $this->stream = $stream;
        $this->bufferSize = $bufferSize;
    }

    /**
     * checks if reached end of stream.
     *
     * @return bool wheather we´re at the end of the stream or not.
     */
    public function eof(): bool
    {
        //Expand buffer by one byte, most reliable method I guess (it has an eof check inbuilt)
        $this->expandBuffer(1);
        return \strlen($this->buffer) <= 0; //There was no way to expand the buffer, basically
    }

    /**
     * peeks the specified length from the given stream and returns it´s assert string.
     *
     * peek won´t move the internal pointer of the stream forward. To move the pointer forward
     * use consume() after peeking.
     *
     * @param int $length the amount of characters to peek (Default: 1).
     * @return string the peeked string.
     */
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

    /**
     * expands the internal buffer by the specified bufferSize if it´s below the specified length.
     *
     * @param int $length the amount of characters to expand the buffer by
     */
    private function expandBuffer(int $length): void
    {
        if (\strlen($this->buffer) >= $length || $this->stream->eof()) {
            return;
        }
        $this->buffer .= $this->stream->read($this->bufferSize);
    }
}
