<?php
declare(strict_types=1);

namespace Tale;

use Psr\Http\Message\StreamInterface;

class Reader
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

    /**
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * @return int
     */
    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    /**
     * @param int $bufferSize
     * @return $this
     */
    public function setBufferSize(int $bufferSize): self
    {
        $this->bufferSize = $bufferSize;
        return $this;
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

    public function consume(int $length = 0): void
    {
        $length = $length !== 0 ? $length : $this->nextConsumeLength;
        if ($length === 0 || $this->eof()) {
            return;
        }
        $this->expandBuffer($length);
        $consumedBytes = substr($this->buffer, 0, $length);
        $this->onConsume($consumedBytes);
        $this->buffer = substr($this->buffer, $length);
        $this->nextConsumeLength = 0;
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

    protected function onConsume(string $bytes): void
    {
    }

    private function expandBuffer(int $length): void
    {
        if (\strlen($this->buffer) >= $length || $this->stream->eof()) {
            return;
        }
        $this->buffer .= $this->stream->read($this->bufferSize);
    }
}
