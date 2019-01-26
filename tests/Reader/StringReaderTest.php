<?php declare(strict_types=1);

namespace Tale\Test;

use PHPUnit\Framework\TestCase;
use Tale\Reader\StreamReader;
use Tale\Reader\StringReader;
use function Tale\stream_memory;

/**
 * @coversDefaultClass \Tale\Reader\StringReader
 */
class StringReaderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::eof
     */
    public function testEof(): void
    {
        $reader = new StringReader('');
        self::assertTrue($reader->eof());

        //Test eof realibility with different stream types


        $reader = new StringReader('a');
        $reader->consume(1);
        self::assertTrue($reader->eof());

        $reader = new StringReader('test');
        self::assertFalse($reader->eof());
    }

    /**
     * @covers ::__construct
     * @covers ::peek
     */
    public function testPeek(): void
    {
        $reader = new StringReader('');
        self::assertSame('', $reader->peek());

        $reader = new StringReader('a');
        self::assertSame('', $reader->peek(2));

        $reader = new StringReader('test');
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
     */
    public function testConsume(): void
    {
        $reader = new StringReader('test');
        $reader->consume(); //Nothing should happen when nothing was peeked
        self::assertSame('t', $reader->peek());
        $reader->consume();
        self::assertSame('es', $reader->peek(2));
        $reader->consume();
        self::assertSame('t', $reader->peek());
    }
}
