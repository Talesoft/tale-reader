<?php
declare(strict_types=1);

namespace Tale\Test;

use PHPUnit\Framework\TestCase;
use Tale\Reader;
use Tale\Stream\MemoryStream;

/**
 * @coversDefaultClass \Tale\Reader
 */
class ReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getStream
     */
    public function testGetStream(): void
    {
        $stream = new MemoryStream();
        $reader = new Reader($stream);
        self::assertSame($stream, $reader->getStream());
    }

    /**
     * @covers ::__construct
     * @covers ::getBufferSize
     * @covers ::setBufferSize
     */
    public function testGetSetBufferSize(): void
    {
        $reader = new Reader(new MemoryStream());
        self::assertSame(1024, $reader->getBufferSize());
        $reader->setBufferSize(2048);
        self::assertSame(2048, $reader->getBufferSize());

        $reader = new Reader(new MemoryStream(), 2048);
        self::assertSame(2048, $reader->getBufferSize());
    }

    /**
     * @covers ::__construct
     * @covers ::eof
     */
    public function testEof(): void
    {
        $reader = new Reader(new MemoryStream(''));
        self::assertTrue($reader->eof());

        //Test eof realibility with different stream types


        $reader = new Reader(new MemoryStream('a'));
        $reader->read();
        self::assertTrue($reader->eof());

        $reader = new Reader(new MemoryStream('test'));
        self::assertFalse($reader->eof());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::expandBuffer
     */
    public function testPeek(): void
    {
        $reader = new Reader(new MemoryStream());
        self::assertSame('', $reader->peek());

        $reader = new Reader(new MemoryStream('a'));
        self::assertSame('', $reader->peek(2));

        $reader = new Reader(new MemoryStream('test'));
        self::assertSame('t', $reader->peek());
        self::assertSame('te', $reader->peek(2));
        self::assertSame('tes', $reader->peek(3));
        self::assertSame('test', $reader->peek(4));
        self::assertSame('', $reader->peek(6));
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::expandBuffer
     * @covers ::onConsume
     */
    public function testConsume(): void
    {
        $reader = new Reader(new MemoryStream('test'));
        $reader->consume(); //Nothing should happen when nothing was peeked
        self::assertSame('t', $reader->peek());
        $reader->consume();
        self::assertSame('es', $reader->peek(2));
        $reader->consume();
        self::assertSame('t', $reader->peek());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::read
     * @covers ::expandBuffer
     */
    public function testRead(): void
    {
        $reader = new Reader(new MemoryStream('test'));
        self::assertSame('t', $reader->read());
        self::assertSame('es', $reader->read(2));
        self::assertSame('t', $reader->read());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::readWhile
     * @covers ::expandBuffer
     */
    public function testReadWhile(): void
    {
        $reader = new Reader(new MemoryStream('testtesttesttesttesttesttestteststopabc'));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readWhile(function (string $bytes) {
            return $bytes !== 'stop';
        }, 4));
        self::assertSame('stop', $reader->read(4));
        $reader = new Reader(new MemoryStream('testtesttesttesttesttesttestteststopabc'));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readWhile(function (string $bytes) {
            return $bytes !== 'stop';
        }, 4, true));
        self::assertSame('abc', $reader->read(3));
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     * @covers ::consume
     * @covers ::readUntil
     * @covers ::expandBuffer
     */
    public function testReadUntil(): void
    {
        $reader = new Reader(new MemoryStream('testtesttesttesttesttesttestteststopabc'));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readUntil(function (string $bytes) {
            return $bytes === 'stop';
        }, 4));
        self::assertSame('stop', $reader->read(4));

        $reader = new Reader(new MemoryStream('testtesttesttesttesttesttestteststopabc'));
        self::assertSame('testtesttesttesttesttesttesttest', $reader->readUntil(function (string $bytes) {
            return $bytes === 'stop';
        }, 4, true));
        self::assertSame('abc', $reader->read(3));
    }
}
